import argparse
import hashlib

import boto3
from collections import defaultdict
from boto3.dynamodb.conditions import Key
from datetime import datetime, UTC
from decimal import Decimal
import json
import time
import math

from botocore.exceptions import ClientError

# Create connections to AWS service
dynamodb = boto3.resource("dynamodb", region_name="eu-west-1")
dynamodb_client = boto3.client("dynamodb", region_name="eu-west-1")
s3 = boto3.client("s3", region_name="eu-west-1")

# CLI arguments & define command line options
def parse_args():
    parser = argparse.ArgumentParser(description="Merge duplicate OPG identities")

    parser.add_argument("--limit", type=int, default=None,
                        help="Limit number of duplicate identities processed")
    parser.add_argument("--offset", type=int, default=0,
                        help="Skip this many duplicate identities before processing")
    parser.add_argument("--table-prefix", default="demo", type=str,
                        help="The DynamoDB table prefix to use when querying. default: demo")
    parser.add_argument("--bucket", type=str,
                        help="The bucket containing merge plans and work files")
    parser.add_argument("--work-prefix", default="todo", type=str,
                        help="Bucket prefix for work files. default: todo")
    parser.add_argument("--plan-prefix", default="plan", type=str,
                        help="Bucket prefix for merge plan files. default: plan")

    parser.add_argument("--plan-file", type=str,
                        help="Specify a specific merge plan file to run")

    parser.add_argument("--execute", action="store_true",
                        help="Apply a reviewed merge plan")

    args = parser.parse_args()

    if not args.bucket:
        raise Exception("Execution requires --bucket (S3 bucket)")

    return args

def get_actor_users_table(prefix):
    return dynamodb.Table(f"{prefix}-ActorUsers")

def get_lpa_table(prefix):
    return dynamodb.Table(f"{prefix}-UserLpaActorMap")

def get_viewer_code_table(prefix):
    return dynamodb.Table(f"{prefix}-ViewerCodes")

# Fetches one UserLpaActorMap by Id
def get_by_id(table, id):
    response = table.get_item(Key={"Id": id})
    return response.get("Item")

# Gets one viewer code row by its viewer code
def get_viewer_code_by_id(viewer_table, viewer_code):
    response = viewer_table.get_item(Key={"ViewerCode": viewer_code})
    return response.get("Item")

# Deteremines the primary - mostly recently logged in
def determine_primary(actor_collection):
    def parse_login(user):
        value = user.get("LastLogin")
        if not value:
            return datetime.min
        try:
            return datetime.fromisoformat(value)
        except:
            return datetime.min

    return max(actor_collection, key=parse_login)

# Fetch LPAs for a user
# Scan whole UserLpaCAtorMap table and returns all mappings for a user [primary & secodary]
def get_user_lpas(lpa_table, user_id):
    response = lpa_table.query(
        IndexName="UserIndex",
        KeyConditionExpression=Key("UserId").eq(user_id)
    )

    items = response.get("Items", [])

    while "LastEvaluatedKey" in response:
        response = lpa_table.query(
            IndexName="UserIndex",
            KeyConditionExpression=Key("UserId").eq(user_id),
            ExclusiveStartKey=response["LastEvaluatedKey"]
        )
        items.extend(response.get("Items", []))

    return items

# Helps return whichever exists
def get_lpa_identifier(mapping):
    return mapping.get("LpaUid") or mapping.get("SiriusUid")

def get_sirius_uid(mapping):
    return mapping.get("SiriusUid")

def get_viewer_codes_for_sirius_uid(viewer_table, sirius_uid):
    response = viewer_table.query(
        IndexName="SiriusUidIndex",
        KeyConditionExpression=Key("SiriusUid").eq(sirius_uid)
    )

    items = response.get("Items", [])

    while "LastEvaluatedKey" in response:
        response = viewer_table.query(
            IndexName="SiriusUidIndex",
            KeyConditionExpression=Key("SiriusUid").eq(sirius_uid),
            ExclusiveStartKey=response["LastEvaluatedKey"]
        )
        items.extend(response.get("Items", []))

    return items

def get_viewer_codes_for_sirius_uid_cached(viewer_table, sirius_uid, cache):
    if sirius_uid not in cache:
        cache[sirius_uid] = get_viewer_codes_for_sirius_uid(viewer_table, sirius_uid)
    return cache[sirius_uid]

def group_viewer_codes_by_mapping(viewer_codes):
    grouped = defaultdict(list)

    for code in viewer_codes:
        mapping_id = code.get("UserLpaActor")
        if mapping_id:
            grouped[mapping_id].append(code)

    return grouped

def choose_canonical_for_group(mappings, viewer_table, primary_user_id, viewer_code_cache):
    sirius_uid = get_sirius_uid(mappings[0])

    if not sirius_uid:
        print(f"WARNING: Missing SiriusUid for mappings {[m['Id'] for m in mappings]}")
        return mappings[0]["Id"], set(), [], {}

    viewer_codes = get_viewer_codes_for_sirius_uid_cached(
        viewer_table,
        sirius_uid,
        viewer_code_cache
    )

    codes_by_mapping = group_viewer_codes_by_mapping(viewer_codes)

    candidates = []

    for mapping in mappings:
        codes = codes_by_mapping.get(mapping["Id"], [])

        candidates.append({
            "mapping": mapping,
            "codes": codes,
            "has_codes": len(codes) > 0,
            "is_primary_owned": mapping.get("UserId") == primary_user_id,
        })

    canonical_entry = sorted(
        candidates,
        key=lambda entry: (
            0 if entry["is_primary_owned"] else 1,
            0 if entry["has_codes"] else 1,
            entry["mapping"]["Id"],
        )
    )[0]

    canonical_mapping = canonical_entry["mapping"]
    canonical_id = canonical_mapping["Id"]

    delete_mapping_ids = set()
    viewer_code_updates = []

    for entry in candidates:
        mapping = entry["mapping"]

        if mapping["Id"] == canonical_id:
            continue

        duplicate_id = mapping["Id"]
        delete_mapping_ids.add(duplicate_id)

        for code in entry["codes"]:
            viewer_code_updates.append({
                "viewer_code": code["ViewerCode"],
                "from_mapping_id": duplicate_id,
                "to_mapping_id": canonical_id,
            })

    return canonical_id, delete_mapping_ids, viewer_code_updates, codes_by_mapping

def get_merge_plan_key(plan_prefix, merge_plan):
    filename = hashlib.md5(merge_plan.get("identity").encode()).hexdigest()

    return f"{plan_prefix}/{filename}.json"

def iterate_merge_plans(bucket, plan_prefix):
    prefix = f"{plan_prefix}/"

    paginator = s3.get_paginator('list_objects_v2')
    page_iterator = paginator.paginate(
        Bucket=bucket,
        Prefix=prefix,
        PaginationConfig={'PageSize': 10}
    )

    for page in page_iterator:
        plans = []

        if page.get("Contents"):
            for plan in page["Contents"]:
                plans.append(plan["Key"])

            yield plans


# Dry-run display function
# Shows which user is retained - the primary user
# Shows which user is merged/deleted - the secondary user
# Shows existing mapping for primary user including viewer codes
# Shows mapping for secondary user including viewer codes
# Shows planned actions
#    - repoint VC
#    - move canonical mapping
#    - delete duplicate mapping
#    - delete secondary account
# Shows expected outcome calling print_expected_outcome(plan)
def print_merge_plan_for_identity(plan):
    print(f"Primary user: {plan['primary_user_id']}")
    print(f"Secondary users: {plan['secondary_user_ids']}")

    print("\nExisting mappings for primary user:")
    primary_found = False
    for mapping in plan["all_mappings"]:
        if mapping["UserId"] == plan["primary_user_id"]:
            primary_found = True
            print(
                f" - MappingId={mapping['Id']} | "
                f"ActorId={mapping['ActorId']} | "
                f"LPA={mapping['LpaIdentifier']}"
            )
            codes = plan["mapping_viewer_codes"].get(mapping["Id"], [])
            if codes:
                print(f"   ViewerCodes: {codes}")
            else:
                print("   ViewerCodes: None")

    if not primary_found:
        print(" - None")

    for secondary_user_id in plan["secondary_user_ids"]:
        print(f"\nExisting mappings for secondary user {secondary_user_id}:")
        found = False
        for mapping in plan["all_mappings"]:
            if mapping["UserId"] == secondary_user_id:
                found = True
                print(
                    f" - MappingId={mapping['Id']} | "
                    f"ActorId={mapping['ActorId']} | "
                    f"LPA={mapping['LpaIdentifier']}"
                )
                codes = plan["mapping_viewer_codes"].get(mapping["Id"], [])
                if codes:
                    print(f"   ViewerCodes: {codes}")
                else:
                    print("   ViewerCodes: None")

        if not found:
            print(" - None")

    print("\nPlanned actions:")
    if not plan["viewer_code_updates"] and not plan["move_mapping_ids"] and not plan["delete_mapping_ids"] and not plan["delete_secondary_user_ids"]:
        print(" - None")

    for update in plan["viewer_code_updates"]:
        print(
            f" - Repoint viewer code {update['viewer_code']} "
            f"from {update['from_mapping_id']} to {update['to_mapping_id']}"
        )

    for mapping in plan["all_mappings"]:
        mapping_id = mapping["Id"]
        actor_id = mapping["ActorId"]
        lpa_id = mapping["LpaIdentifier"]

        if mapping_id in plan["move_mapping_ids"]:
            print(
                f" - Move canonical mapping {mapping_id} "
                f"(ActorId {actor_id}, LPA {lpa_id}) to {plan['primary_user_id']}"
            )

        if mapping_id in plan["delete_mapping_ids"]:
            print(
                f" - Delete duplicate mapping {mapping_id} "
                f"(ActorId {actor_id}, LPA {lpa_id})"
            )

    for secondary_user_id in plan["delete_secondary_user_ids"]:
        print(f" - Delete secondary account {secondary_user_id}")

    print_expected_outcome(plan)


# Display function for dry-run output
# Prints the plan for a single duplicate identity group...will show
# current state
# planned actions
# expected outcome
def print_expected_outcome(plan):
    print("\nExpected outcome after execution:")

    surviving_mappings = []

    for mapping in plan["all_mappings"]:
        mapping_id = mapping["Id"]

        if mapping_id in plan["delete_mapping_ids"]:
            continue

        final_user_id = mapping["UserId"]
        if mapping_id in plan["move_mapping_ids"]:
            final_user_id = plan["primary_user_id"]

        surviving_mappings.append({
            "Id": mapping_id,
            "UserId": final_user_id,
            "ActorId": mapping["ActorId"],
            "LpaIdentifier": mapping["LpaIdentifier"],
        })

    print("\nRemaining ActorUsers:")
    print(f" - {plan['primary_user_id']}")
    if plan["delete_secondary_user_ids"]:
        print("Deleted ActorUsers:")
        for user_id in plan["delete_secondary_user_ids"]:
            print(f" - {user_id}")

    print("\nRemaining UserLpaActorMap rows:")
    for mapping in surviving_mappings:
        print(
            f" - MappingId={mapping['Id']} | "
            f"UserId={mapping['UserId']} | "
            f"ActorId={mapping['ActorId']} | "
            f"LPA={mapping['LpaIdentifier']}"
        )

    print("\nViewerCodes after execution:")
    repointed_codes = {
        update["viewer_code"]: update["to_mapping_id"]
        for update in plan["viewer_code_updates"]
    }

    shown_any = False
    for mapping in plan["all_mappings"]:
        original_mapping_id = mapping["Id"]
        viewer_codes = plan["mapping_viewer_codes"].get(original_mapping_id, [])

        for viewer_code in viewer_codes:
            final_mapping_id = repointed_codes.get(viewer_code, original_mapping_id)

            if final_mapping_id in plan["delete_mapping_ids"]:
                continue

            print(f" - {viewer_code} -> {final_mapping_id}")
            shown_any = True

    if not shown_any:
        print(" - None")


# This groups identity mappings by same actor, same LPA - logical duplicates
# Helps to meet rule - multiple LPAs share the same actor ID delete all but one
def group_mappings_by_logical_key(mappings):
    grouped = defaultdict(list)

    for mapping in mappings:
        key = (clean(mapping.get("ActorId")), get_lpa_identifier(mapping))
        grouped[key].append(mapping)

    return grouped


#   Choose one canonical mapping id to keep for that group
#   For each duplicate loggical mapping group
#   If only one mapping exist - keep it
#   If multuple mapping exist
#       - choose one canocical mapping [CM] to keep
#       - evey other becomes a duplicate to delete
#       - any viewer code pointing to duplicate mapping ids are repoinred to the CM id
#   To meet the rule- only one mapping row should remain per (ActorId, LPA) and all viewer codes should point to that same surviving mapping id
def choose_canonical_mappings(grouped_mappings, viewer_table, primary_user_id, viewer_code_cache):
    canonical_mapping_ids = set()
    delete_mapping_ids = set()
    viewer_code_updates = []
    all_codes_by_mapping = {}

    for _, mappings in grouped_mappings.items():
        if len(mappings) == 1:
            canonical_mapping_ids.add(mappings[0]["Id"])
            continue

        (
            canonical_id,
            group_delete_ids,
            group_viewer_updates,
            codes_by_mapping
        ) = choose_canonical_for_group(
            mappings,
            viewer_table,
            primary_user_id,
            viewer_code_cache
        )

        canonical_mapping_ids.add(canonical_id)
        delete_mapping_ids.update(group_delete_ids)
        viewer_code_updates.extend(group_viewer_updates)

        all_codes_by_mapping.update(codes_by_mapping)

    return canonical_mapping_ids, delete_mapping_ids, viewer_code_updates, all_codes_by_mapping


# The main function that builds the actual plan for one duplicate identity
# Choose primary user
# Load all mappings for all users in the identity group
# Group mappings by logical duplicates Actor Id and LPA
# Decide which mapping to keep/delete
# Decide which mapping moves to primary user
# Decide viewer codes to repoint to mapping if any
# Mark secodary user for deletion
def build_merge_plan_for_identity(identity, actors, lpa_table, viewer_table, viewer_code_cache):
    primary = determine_primary(actors)
    secondaries = [u for u in actors if u["Id"] != primary["Id"]]

    all_mappings = []
    for user in actors:
        user_mappings = get_user_lpas(lpa_table, user["Id"])
        all_mappings.extend(user_mappings)

    grouped_mappings = group_mappings_by_logical_key(all_mappings)
    (
        canonical_mapping_ids,
        delete_mapping_ids,
        viewer_code_updates,
        codes_by_mapping
    ) = choose_canonical_mappings(
        grouped_mappings,
        viewer_table,
        primary["Id"],
        viewer_code_cache
    )

    move_mapping_ids = []
    for mapping in all_mappings:
        if mapping["Id"] in canonical_mapping_ids and mapping.get("UserId") != primary["Id"]:
            move_mapping_ids.append(mapping["Id"])

    compact_mappings = []
    for mapping in all_mappings:
        compact_mappings.append({
            "Id": mapping["Id"],
            "UserId": mapping.get("UserId"),
            "ActorId": clean(mapping.get("ActorId")),
            "LpaIdentifier": get_lpa_identifier(mapping),
        })

    mapping_viewer_codes = {}

    for mapping in all_mappings:
        codes = codes_by_mapping.get(mapping["Id"], [])
        mapping_viewer_codes[mapping["Id"]] = [
            code["ViewerCode"] for code in codes
        ]

    return {
        "identity": identity,
        "primary_user_id": primary["Id"],
        "secondary_user_ids": [u["Id"] for u in sorted(secondaries, key=lambda u: u["Id"])],
        "all_mappings": sorted(compact_mappings, key=lambda m: m["Id"]),
        "canonical_mapping_ids": sorted(list(canonical_mapping_ids)),
        "move_mapping_ids": sorted(list(move_mapping_ids)),
        "delete_mapping_ids": sorted(list(delete_mapping_ids)),
        "viewer_code_updates": sorted(viewer_code_updates, key=lambda m: m["viewer_code"]),
        "mapping_viewer_codes": {key:mapping_viewer_codes[key] for key in sorted(mapping_viewer_codes)},
        "delete_secondary_user_ids": [u["Id"] for u in sorted(secondaries, key=lambda u: u["Id"])],
    }

# runs with --execute option
# 1. Move canocical mappings to primary
# 2. Repoint viewer codes
# 3. Delete duplicate mappings
# 4. Delete secondary accounts
def execute_merge_plan(table_prefix, merge_plan, viewer_code_cache=None):
    if viewer_code_cache is None:
        viewer_code_cache = {}

    execution_start = time.perf_counter()

    lpa_table = get_lpa_table(table_prefix)
    viewer_table = get_viewer_code_table(table_prefix)

    print("\n" + "=" * 100)
    print(f"Executing identity: {merge_plan['identity']}")

    # generate a plan based on current data. it will need to match the stored plan
    actors = []
    for actor in [merge_plan['primary_user_id']] + merge_plan['secondary_user_ids']:
        actors.append(populate_actor(table_prefix, actor))

    current_merge_plan = build_merge_plan_for_identity(
        merge_plan['identity'],
        actors,
        lpa_table,
        viewer_table,
        viewer_code_cache
    )

    if current_merge_plan != merge_plan:
        print(f"ERR - Merge plan does not match stored plan. Skipping.")
        print(f"{current_merge_plan}")
        print(f"{merge_plan}")
        return

    primary_user_id = merge_plan["primary_user_id"]
    print(f"Primary user: {primary_user_id}")

    transaction = []

    # 1. Move canonical mappings to primary
    for mapping_id in merge_plan["move_mapping_ids"]:
        current_user_id = [m for m in merge_plan.get("all_mappings") if m.get("Id") == mapping_id][0].get("UserId")

        transaction.append(
            {
                "Update": {
                    "TableName": f"{table_prefix}-UserLpaActorMap",
                    "Key": {"Id": {"S": mapping_id}},
                    "UpdateExpression": "SET UserId = :new_user",
                    "ConditionExpression": "UserId = :expected_user",
                    "ExpressionAttributeValues": {
                        ":new_user": {"S": primary_user_id},
                        ":expected_user": {"S": current_user_id}
                    }
                }
            }
        )

        print(f" - MOVE mapping {mapping_id}: {current_user_id} -> {primary_user_id}")

    # 2. Repoint viewer codes to canonical mapping ids
    for update in merge_plan["viewer_code_updates"]:
        transaction.append(
            {
                "Update": {
                    "TableName": f"{table_prefix}-ViewerCodes",
                    "Key": {"ViewerCode": {"S": update.get("viewer_code")}},
                    "UpdateExpression": "SET UserLpaActor = :new_mapping",
                    "ConditionExpression": "UserLpaActor = :expected_mapping",
                    "ExpressionAttributeValues": {
                        ":new_mapping": {"S": update.get("to_mapping_id")},
                        ":expected_mapping": {"S": update.get("from_mapping_id")}
                    }
                }
            }
        )

        print(
            f" - UPDATE viewer code {update.get("viewer_code")}: ",
            f"{update.get("from_mapping_id")} -> {update.get("to_mapping_id")}"
        )

    for mapping_id in merge_plan["delete_mapping_ids"]:
        transaction.append(
            {
                "Delete": {
                    "TableName": f"{table_prefix}-UserLpaActorMap",
                    "Key": {"Id": {"S": mapping_id}},
                    "ConditionExpression": "attribute_exists(UserId)",
                }
            }
        )

        print(f" - DELETE duplicate mapping {mapping_id}")

    for secondary_user_id in merge_plan["delete_secondary_user_ids"]:
        transaction.append(
            {
                "Delete": {
                    "TableName": f"{table_prefix}-ActorUsers",
                    "Key": {"Id": {"S": secondary_user_id}},
                    "ConditionExpression": "attribute_exists(#sub)",
                    "ExpressionAttributeNames": {"#sub": "Identity"}
                }
            }
        )

        print(f" - DELETE secondary account {secondary_user_id}")

    # throws exception that callers will deal with
    dynamodb_client.transact_write_items(TransactItems=transaction)

    print("\nExecution complete")

    execution_duration = time.perf_counter() - execution_start
    print(f"\nExecution time: {execution_duration:.3f} seconds")

def execute_all_plans(bucket, plan_prefix, table_prefix):
    # cache for viewer codes by SiriusUid
    viewer_code_cache = {}

    print(f"Executing all plans in \"{bucket}/{plan_prefix}/\"")
    for merge_plans in iterate_merge_plans(bucket, plan_prefix):
        for merge_plan_key in merge_plans:
            plan = load_from_s3(bucket, merge_plan_key)

            try:
                execute_merge_plan(table_prefix, plan, viewer_code_cache)
                move_plan_to_done(bucket, merge_plan_key)
            except ClientError as e:
                print(f" - ERR Plan could not be applied: {e}")

# Export the merge plan to JSON before execution for review, migration artifact - save to S3
def save_merge_plan_s3(bucket, plan_prefix, merge_plan):
    key = get_merge_plan_key(plan_prefix, merge_plan)

    s3.put_object(
        Bucket=bucket,
        Key=key,
        Body=json.dumps(merge_plan, indent=2),
        ContentType="application/json",
        Metadata={
            "identity": merge_plan.get("identity"),
            "primary-id": merge_plan.get("primary_user_id")
        }
    )

    print(f"\nMerge plan saved to S3:")
    print(f"s3://{bucket}/{key}")

    return key

# Helper to convert Dynamodb decimal to plain int
def clean(value):
    if isinstance(value, Decimal):
        return int(value)
    return value

# Load merge plan from S3
def load_from_s3(bucket, plan_file):
    response = s3.get_object(
        Bucket=bucket,
        Key=f"{plan_file}"
    )

    body = response["Body"].read()
    return json.loads(body)

def move_plan_to_done(bucket, plan_file):
    s3.copy_object(
        Bucket=bucket,
        CopySource=f"{bucket}/{plan_file}",
        Key=f"done/{plan_file}"
    )

    s3.delete_object(
        Bucket=bucket,
        Key=f"{plan_file}"
    )

def load_work_files(bucket, work_prefix, limit=None, offset=0):
    print(f" - Loading work files from {work_prefix} with offset {offset} and limit {limit}")

    # decide what files to pull
    # pulls a max of 1000 - we'll never see it
    all_work_files = s3.list_objects_v2(Bucket=bucket, Prefix=(work_prefix + "/duplicate-identities"))
    print(f" - Found {len(all_work_files["Contents"])} work files")

    # calculate limit/offset as work-files.
    # work-files contain up to 100 records
    first_file = math.ceil(offset/100)
    last_file  = math.ceil((offset + limit)/100) if limit is not None else len(all_work_files["Contents"])
    file_range = list(range(first_file, last_file + 1))

    print(f" - Expecting to start at file {first_file} and load {len(file_range) - 1} additional work files")

    # load our work files and pull identities from them
    work_file_keys = []
    for file in all_work_files["Contents"]:
        file_no = int(file["Key"].removeprefix(f"{work_prefix}/duplicate-identities-").removesuffix(".json"))
        if file_no in file_range:
            work_file_keys.append(file["Key"])

    identities = []
    for work_file_key in work_file_keys:
        file = s3.get_object(Bucket=bucket , Key=work_file_key)
        identities = identities + json.loads(file["Body"].read())

    # pare down identities to the required ones by relative offsets
    start_record = offset % 100
    end_record   = (start_record + limit) if limit is not None else None
    identities   = identities[start_record:end_record]

    print(f" - Loaded {len(identities)} identities from {len(work_file_keys)} work files")
    return identities


def populate_work_items(table_prefix, work_items):
    print("\n" + "=" * 100)
    print(f"Populating work items...")

    populated_items = []

    for work_item in work_items:
        try:
            actors = []
            for ids in work_item.get("user_ids"):
                actors.append(populate_actor(table_prefix, ids))

            work_item["actors"] = actors
            populated_items.append(work_item)
        except RuntimeError as err:
            print(err)

    return populated_items

def populate_actor(table_prefix, id):
    actor = get_by_id(get_actor_users_table(table_prefix), id)
    if actor is None:
        raise RuntimeError(f" - Actor with id {id} not found, not continuing with this Identity")

    return actor

def build_plans(table_prefix, bucket, work_prefix, plan_prefix, limit=None, offset=0):
    script_start = time.perf_counter()

    lpa_table    = get_lpa_table(table_prefix)
    viewer_table = get_viewer_code_table(table_prefix)

    # cache for viewer codes by SiriusUid
    viewer_code_cache = {}

    # output of this will be a dict containing user accounts sharing a single identity
    # e.g. [identity:actor_with_id1, actor_with_id2, actor_with_id3]]
    print("Loading work items...")
    work_items = load_work_files(bucket, work_prefix, limit, offset)
    duplicates = populate_work_items(table_prefix, work_items)

    merge_plan = []

    for identity in duplicates:
        identity_start = time.perf_counter()

        print("=" * 100)
        print(f"Identity: {identity.get("identity")}")
        plan = build_merge_plan_for_identity(
            identity.get("identity"),
            identity.get("actors"),
            lpa_table,
            viewer_table,
            viewer_code_cache
        )

        print_merge_plan_for_identity(plan)

        save_merge_plan_s3(bucket, plan_prefix, plan)
        merge_plan.append(plan)

        identity_duration = time.perf_counter() - identity_start
        print(f"\nIdentity processing time: {identity_duration:.3f} seconds")

    print("\n" + "=" * 100)
    print("MERGE PLAN SUMMARY")
    print("=" * 100)

    for plan in merge_plan:
        print(
            f"{plan['identity']} | "
            f"primary={plan['primary_user_id']} | "
            f"moves={len(plan['move_mapping_ids'])} | "
            f"deletes={len(plan['delete_mapping_ids'])} | "
            f"secondary_users={len(plan['delete_secondary_user_ids'])}"
        )

    if not merge_plan:
        print("\n" + "=" * 100)
        print("No duplicate identities selected for this batch.")

    total_duration = time.perf_counter() - script_start

    print("\n" + "=" * 100)
    print(f"Processed {len(merge_plan)} identity group(s) in this batch")
    print(f"TOTAL SCRIPT RUNTIME: {total_duration:.3f} seconds")
    print("=" * 100)

def main():
    args = parse_args()

    if args.execute:
        if args.plan_file:
            print(f"Using merge plan: {args.plan_prefix}/{args.plan_file}")
            merge_plan_key = f"{args.plan_prefix}/{args.plan_file}"
            merge_plan = load_from_s3(args.bucket, merge_plan_key)

            if merge_plan:
                print(f" - Found plan for {merge_plan.get("identity")} - executing")

                try:
                    execute_merge_plan(args.table_prefix, merge_plan)
                    move_plan_to_done(args.bucket, merge_plan_key)
                except ClientError as e:
                    print(f" - ERR Plan could not be applied: {e}")
        else:
            execute_all_plans(args.bucket, args.plan_prefix, args.table_prefix)

        return

    build_plans(
        table_prefix=args.table_prefix,
        bucket=args.bucket,
        work_prefix=args.work_prefix,
        plan_prefix=args.plan_prefix,
        limit=args.limit,
        offset=args.offset,
    )


if __name__ == "__main__":
    main()
