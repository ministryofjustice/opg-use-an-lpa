import argparse
import boto3
from collections import defaultdict
from boto3.dynamodb.conditions import Key
from datetime import datetime, UTC
from decimal import Decimal
import json
import time
import os

#######
# 1. Connect to AWS account
# Assume the correct role
# Create Dynamo client using those temporary credentials
#######

AWS_ACCOUNT_IDS = {
#     "production": "690083044361",
#     "preproduction": "888228022356",
     "development": "367815980639",
      "demo": "367815980639",
}

#Assume AWS role
def assume_role(environment):
    account_id = AWS_ACCOUNT_IDS[environment]

    if environment == "production":
        role_name = "db-analysis"
    else:
        role_name = "operator"

    role_arn = f"arn:aws:iam::{account_id}:role/{role_name}"

    sts = boto3.client("sts", region_name="eu-west-1")
    session = sts.assume_role(
        RoleArn=role_arn,
        RoleSessionName="duplicate_identity_merge",
        DurationSeconds=900,
    )

    return session["Credentials"]

# CLI arguments & define command line options
def parse_args():
    parser = argparse.ArgumentParser(description="Merge duplicate OPG identities")

    parser.add_argument(
        "--environment",
        required=True,
        help="Environment: development | demo | production"
    )

    parser.add_argument(
        "--limit",
        type=int,
        help="Limit number of duplicate identities processed"
    )

    parser.add_argument(
        "--offset",
        type=int,
        default=0,
        help="Skip this many duplicate identities before processing"
    )

    parser.add_argument(
        "--execute",
        action="store_true",
        help="Apply a reviewed merge plan"
    )

    parser.add_argument(
        "--output-dir",
        default=".",
        help="Directory to save merge plan files"
    )

    parser.add_argument(
        "--plan-file",
        help="Path to a previously generated merge plan JSON file"
    )

    return parser.parse_args()

# Connecting to dynamodb, this returns ActorUsers table
# To identify duplicate identities
# To idetify primary user
# To identify secondary user
def get_dynamo_table(environment):
    creds = assume_role(environment)

    dynamodb = boto3.resource(
        "dynamodb",
        region_name="eu-west-1",
        aws_access_key_id=creds["AccessKeyId"],
        aws_secret_access_key=creds["SecretAccessKey"],
        aws_session_token=creds["SessionToken"],
    )

    TABLE_PREFIX = {
#         "production": "ual-prod",
#         "preproduction": "ual-preprod",
        "development": "3644uml4037",
        "demo": "demo",
    }

    return dynamodb.Table(f"{TABLE_PREFIX[environment]}-ActorUsers")

# Scan Actor Users table to detect duplicate identities
def scan_actor_users(table):
    response = table.scan()
    items = response["Items"]

    while "LastEvaluatedKey" in response:
        response = table.scan(ExclusiveStartKey=response["LastEvaluatedKey"])
        items.extend(response["Items"])

    return items

# Groups users by identity
# Rule says find duplicate accounts based on One Login subject
def group_by_identity(users):
    grouped = defaultdict(list)

    for user in users:
        identity = user.get("Identity")
        if identity:
            grouped[identity].append(user)

    return grouped

# Loads and returns the UserLpaActorMap table
# Maps the user-actor relationship on a LPA
def get_lpa_table(environment):
    creds = assume_role(environment)

    dynamodb = boto3.resource(
        "dynamodb",
        region_name="eu-west-1",
        aws_access_key_id=creds["AccessKeyId"],
        aws_secret_access_key=creds["SecretAccessKey"],
        aws_session_token=creds["SessionToken"],
    )

    TABLE_PREFIX = {
       "development": "3644uml4037",
        "demo": "demo"
    }

    return dynamodb.Table(f"{TABLE_PREFIX[environment]}-UserLpaActorMap")


# Loads and returns the ViewerCodes table
# If duplicate mapping exists and has VC, must be repointed to the one canonical mapping that survives
def get_viewer_code_table(environment):
    creds = assume_role(environment)

    dynamodb = boto3.resource(
        "dynamodb",
        region_name="eu-west-1",
        aws_access_key_id=creds["AccessKeyId"],
        aws_secret_access_key=creds["SecretAccessKey"],
        aws_session_token=creds["SessionToken"],
    )

    table_prefix = {
        "development": "3644uml4037",
        "demo": "demo",
    }

    return dynamodb.Table(f"{table_prefix[environment]}-ViewerCodes")

# Helper table accessor
def get_actor_users_table(environment):
    return get_dynamo_table(environment)

# Fetches one UserLpaActorMap by Id
def get_mapping_by_id(lpa_table, mapping_id):
    response = lpa_table.get_item(Key={"Id": mapping_id})
    return response.get("Item")

# Fetches one ActorUsers row by Id
def get_actor_user_by_id(actor_table, user_id):
    response = actor_table.get_item(Key={"Id": user_id})
    return response.get("Item")


# Deteremines the primary - mostly recently logged in
def determine_primary(group):
    def login_value(user):
        return user.get("LastLogin") or ""
    return max(group, key=login_value)


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


# This returns all viewer coes associated to a given mapping
def get_viewer_codes_for_mapping(viewer_table, mapping_id):
    response = viewer_table.scan(
        FilterExpression="UserLpaActor = :mapping_id",
        ExpressionAttributeValues={":mapping_id": mapping_id}
    )

    items = response.get("Items", [])

    while "LastEvaluatedKey" in response:
        response = viewer_table.scan(
            FilterExpression="UserLpaActor = :mapping_id",
            ExpressionAttributeValues={":mapping_id": mapping_id},
            ExclusiveStartKey=response["LastEvaluatedKey"]
        )
        items.extend(response.get("Items", []))

    return items


# Gets one viewer code row by its code
def get_viewer_code_by_id(viewer_table, viewer_code):
    response = viewer_table.get_item(Key={"ViewerCode": viewer_code})
    return response.get("Item")


# Helps return whichever exists
def get_lpa_identifier(mapping):
    return mapping.get("LpaUid") or mapping.get("SiriusUid")

def choose_canonical_for_group(mappings, viewer_table, primary_user_id):
    candidates = []

    for mapping in mappings:
        codes = get_viewer_codes_for_mapping(viewer_table, mapping["Id"])
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

    return canonical_id, delete_mapping_ids, viewer_code_updates


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


# This groups identofies mappings by same actor, same LPA - logical duplicates
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
def choose_canonical_mappings(grouped_mappings, viewer_table, primary_user_id):
    canonical_mapping_ids = set()
    delete_mapping_ids = set()
    viewer_code_updates = []

    for _, mappings in grouped_mappings.items():
        if len(mappings) == 1:
            canonical_mapping_ids.add(mappings[0]["Id"])
            continue

        canonical_id, group_delete_ids, group_viewer_updates = choose_canonical_for_group(
            mappings,
            viewer_table,
            primary_user_id,
        )

        canonical_mapping_ids.add(canonical_id)
        delete_mapping_ids.update(group_delete_ids)
        viewer_code_updates.extend(group_viewer_updates)

    return canonical_mapping_ids, delete_mapping_ids, viewer_code_updates


# The main function that builds the actual plan for one duplicate identity
# Choose primary user
# Load all mappings for all users in the identity group
# Group mappings by logical duplicates Actor Id and LPA
# Decide which mapping to keep/delete
# Decide which mapping moves to primary user
# Decide viewer codes to repoint to mapping if any
# Mark secodary user for deletion
def build_merge_plan_for_identity(identity, group, viewer_table, lpa_table):
    primary = determine_primary(group)
    secondaries = [u for u in group if u["Id"] != primary["Id"]]

    all_mappings = []
    for user in group:
        user_mappings = get_user_lpas(lpa_table, user["Id"])
        all_mappings.extend(user_mappings)

    grouped_mappings = group_mappings_by_logical_key(all_mappings)
    canonical_mapping_ids, delete_mapping_ids, viewer_code_updates = choose_canonical_mappings(
        grouped_mappings,
        viewer_table,
        primary["Id"]
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
        codes = get_viewer_codes_for_mapping(viewer_table, mapping["Id"])
        mapping_viewer_codes[mapping["Id"]] = [
            code["ViewerCode"] for code in codes
        ]

    return {
        "identity": identity,
        "primary_user_id": primary["Id"],
        "secondary_user_ids": [u["Id"] for u in secondaries],
        "all_mappings": compact_mappings,
        "canonical_mapping_ids": sorted(list(canonical_mapping_ids)),
        "move_mapping_ids": sorted(list(move_mapping_ids)),
        "delete_mapping_ids": sorted(list(delete_mapping_ids)),
        "viewer_code_updates": viewer_code_updates,
        "mapping_viewer_codes": mapping_viewer_codes,
        "delete_secondary_user_ids": [u["Id"] for u in secondaries],
    }

# runs with --execute option
# 1. Move canocical mappings to primary
# 2. Repoint viewer codes
# 3. Delete duplicate mappings
# 4. Delete secondary accounts
def execute_merge_plan(environment, merge_plan):
    execution_start = time.perf_counter()

    print("\nExecuting merge plan...")

    actor_table = get_actor_users_table(environment)
    lpa_table = get_lpa_table(environment)
    viewer_table = get_viewer_code_table(environment)

    for plan in merge_plan:
        print("\n" + "=" * 60)
        print(f"Executing identity: {plan['identity']}")
        print(f"Primary user: {plan['primary_user_id']}")

        primary_user_id = plan["primary_user_id"]

        # 1. Move canonical mappings to primary
        for mapping_id in plan["move_mapping_ids"]:
            mapping = get_mapping_by_id(lpa_table, mapping_id)

            if not mapping:
                print(f" - SKIP move {mapping_id}: mapping not found")
                continue

            current_user_id = mapping.get("UserId")
            if current_user_id == primary_user_id:
                print(f" - SKIP move {mapping_id}: already belongs to primary")
                continue

            print(f" - MOVE mapping {mapping_id}: {current_user_id} -> {primary_user_id}")

            lpa_table.update_item(
                Key={"Id": mapping_id},
                UpdateExpression="SET UserId = :new_user",
                ExpressionAttributeValues={":new_user": primary_user_id}
            )

        # 2. Repoint viewer codes to canonical mapping ids
        for update in plan["viewer_code_updates"]:
            viewer_code = update["viewer_code"]
            to_mapping_id = update["to_mapping_id"]

            code_row = get_viewer_code_by_id(viewer_table, viewer_code)
            if not code_row:
                print(f" - SKIP viewer code {viewer_code}: not found")
                continue

            current_mapping_id = code_row.get("UserLpaActor")
            if current_mapping_id == to_mapping_id:
                print(f" - SKIP viewer code {viewer_code}: already repointed")
                continue

            print(
                f" - UPDATE viewer code {viewer_code}: "
                f"{current_mapping_id} -> {to_mapping_id}"
            )

            viewer_table.update_item(
                Key={"ViewerCode": viewer_code},
                UpdateExpression="SET UserLpaActor = :new_mapping",
                ExpressionAttributeValues={":new_mapping": to_mapping_id}
            )

        # 3. Delete duplicate mappings
        for mapping_id in plan["delete_mapping_ids"]:
            mapping = get_mapping_by_id(lpa_table, mapping_id)

            if not mapping:
                print(f" - SKIP delete mapping {mapping_id}: already deleted")
                continue

            print(f" - DELETE duplicate mapping {mapping_id}")

            lpa_table.delete_item(Key={"Id": mapping_id})

        # 4. Delete secondary accounts
        for secondary_user_id in plan["delete_secondary_user_ids"]:
            actor_user = get_actor_user_by_id(actor_table, secondary_user_id)

            if not actor_user:
                print(f" - SKIP delete user {secondary_user_id}: already deleted")
                continue

            print(f" - DELETE secondary account {secondary_user_id}")

            actor_table.delete_item(Key={"Id": secondary_user_id})

        mark_identity_completed(environment, plan["identity"])
        print(f" - CHECKPOINT saved for identity {plan['identity']}")

    print("\nExecution complete")

    execution_duration = time.perf_counter() - execution_start
    print(f"\nExecution time: {execution_duration:.3f} seconds")


#Export the merge plan to JSON before execution for review, migration artifact
def save_merge_plan(environment, merge_plan, output_dir="."):
    os.makedirs(output_dir, exist_ok=True)

    timestamp = datetime.now(UTC).strftime("%Y%m%d_%H%M%S")
    filename = os.path.join(output_dir, f"merge_plan_{environment}_{timestamp}.json")

    with open(filename, "w") as f:
        json.dump(merge_plan, f, indent=2)

    print(f"\nMerge plan saved to: {filename}")
    return filename


# Checkpoint helpers
# After each identity is executed successfully, the script will record that identity in a local checkpoint file.
# it will:
# load the checkpoint file
# skip identities already completed
# continue with the remaining ones
def get_checkpoint_filename(environment):
    return f"merge_checkpoint_{environment}.json"


def load_checkpoint(environment):
    filename = get_checkpoint_filename(environment)

    if not os.path.exists(filename):
        return {"completed_identities": []}

    with open(filename, "r") as f:
        return json.load(f)


def save_checkpoint(environment, checkpoint):
    filename = get_checkpoint_filename(environment)

    with open(filename, "w") as f:
        json.dump(checkpoint, f, indent=2)


def mark_identity_completed(environment, identity):
    checkpoint = load_checkpoint(environment)

    completed = set(checkpoint.get("completed_identities", []))
    completed.add(identity)

    checkpoint["completed_identities"] = sorted(list(completed))
    save_checkpoint(environment, checkpoint)


# Helper to convert Dynamodb decimal to plain int
def clean(value):
    if isinstance(value, Decimal):
        return int(value)
    return value

def load_merge_plan(plan_file):
    with open(plan_file, "r") as f:
        return json.load(f)


#   It does:
#   Connect to all required tables
#   Scan all users
#   Group users by identity
#   Filter duplicate identities
#   For each duplicate group:
#         - build merge plan
#         - print plan
#         - collect plan into merge_plan
#   Print a summary
#   Save the merge plan JSON
#   optionally ask for execute confirmation
def run_plan(environment, limit=None, offset=0, execute=False, output_dir="."):
    script_start = time.perf_counter()

    if environment == "production":
        raise Exception("Refusing to run against production without explicit approval.")

    print("Scanning ActorUsers...")

    actor_table = get_dynamo_table(environment)
    lpa_table = get_lpa_table(environment)
    viewer_table = get_viewer_code_table(environment)

    users = scan_actor_users(actor_table)
    grouped = group_by_identity(users)

    duplicates = {
        identity: group
        for identity, group in grouped.items()
        if len(group) > 1
    }

    checkpoint = load_checkpoint(environment)
    completed_identities = set(checkpoint.get("completed_identities", []))

    if completed_identities:
        print(f"Checkpoint loaded: {len(completed_identities)} identities already completed")

    duplicates = {
        identity: group
        for identity, group in duplicates.items()
        if identity not in completed_identities
    }

    duplicate_items = sorted(duplicates.items(), key=lambda x: x[0])

    slice_start = offset
    slice_end = offset + limit if limit is not None else None
    selected_items = duplicate_items[slice_start:slice_end]

    print(f"Found {len(duplicates)} duplicate identities total")
    print(f"Processing identities from offset {slice_start} to {slice_end}\n")

    processed = 0
    merge_plan = []

    for identity, group in selected_items:
        identity_start = time.perf_counter()

        print("=" * 100)
        print(f"Identity: {identity}")

        plan = build_merge_plan_for_identity(identity, group, viewer_table, lpa_table)
        print_merge_plan_for_identity(plan)

        merge_plan.append(plan)
        processed += 1

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
        print("No duplicate identities selected for this batch.")
    else:
        save_merge_plan(environment, merge_plan, output_dir)

    total_duration = time.perf_counter() - script_start

    print("\n" + "=" * 100)
    print(f"Processed {processed} identity group(s) in this batch")
    print(f"TOTAL SCRIPT RUNTIME: {total_duration:.3f} seconds")
    print("=" * 100)

def main():
    args = parse_args()

    if args.execute:
        if not args.plan_file:
            raise Exception("Execution requires --plan-file pointing to a reviewed merge plan JSON")

        if args.environment == "production":
            confirm = input("You are about to modify PRODUCTION. Type PRODUCTION to continue: ")
            if confirm != "PRODUCTION":
                print("Execution cancelled")
                return

        merge_plan = load_merge_plan(args.plan_file)
        execute_merge_plan(args.environment, merge_plan)
        return

    run_plan(
        environment=args.environment,
        limit=args.limit,
        offset=args.offset,
        execute=False,
        output_dir=args.output_dir,
    )


if __name__ == "__main__":
    main()
