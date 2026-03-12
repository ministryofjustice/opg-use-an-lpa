import argparse
import boto3
from collections import defaultdict
from datetime import datetime, UTC
from decimal import Decimal
import json


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

# CLI arguments
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
        "--execute",
        action="store_true",
        help="Apply the merge plan (default is dry-run)"
    )

    return parser.parse_args()

#Connecting to dynamodb, this returns ActorUsers table
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
        "development": "3629uml4270",
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

#Groups users by identity
def group_by_identity(users):
    grouped = defaultdict(list)

    for user in users:
        identity = user.get("Identity")
        if identity:
            grouped[identity].append(user)

    return grouped

# Loads and returns the UserLpaActorMap table
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
        "development": "3629uml4270",
        "demo": "demo"
    }

    return dynamodb.Table(f"{TABLE_PREFIX[environment]}-UserLpaActorMap")


# Loads and returns the ViewerCodes table
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
        "development": "3629uml4270",
        "demo": "demo",
    }

    return dynamodb.Table(f"{table_prefix[environment]}-ViewerCodes")


def get_actor_users_table(environment):
    return get_dynamo_table(environment)


def get_mapping_by_id(lpa_table, mapping_id):
    response = lpa_table.get_item(Key={"Id": mapping_id})
    return response.get("Item")


def get_actor_user_by_id(actor_table, user_id):
    response = actor_table.get_item(Key={"Id": user_id})
    return response.get("Item")

# Fetch LPAs for a user
# Scan whole UserLpaCAtorMap table and returns all mappings for a user
def get_user_lpas(lpa_table, user_id):

    response = lpa_table.scan(
        FilterExpression="UserId = :uid",
        ExpressionAttributeValues={":uid": user_id}
    )

    items = response.get("Items", [])

    while "LastEvaluatedKey" in response:
        response = lpa_table.scan(
            FilterExpression="UserId = :uid",
            ExpressionAttributeValues={":uid": user_id},
            ExclusiveStartKey=response["LastEvaluatedKey"]
        )
        items.extend(response.get("Items", []))

    return items


# Deteremines the primary - mostly recently logged in
def determine_primary(group):
    def login_value(user):
        return user.get("LastLogin") or ""
    return max(group, key=login_value)


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

def get_lpa_identifier(mapping):
    return mapping.get("LpaUid") or mapping.get("SiriusUid")


# This groups mappings by same actor, same LPA
def group_mappings_by_logical_key(mappings):
    grouped = defaultdict(list)

    for mapping in mappings:
        key = (clean(mapping.get("ActorId")), get_lpa_identifier(mapping))
        grouped[key].append(mapping)

    return grouped

# For each duplicate identity
# If only one mapping - keep it
# If multiple mapping and exactly one has viewer code, keep the referenced mapping and delete others
# Multiple mappings and more than one has viewer codes, keep all referenced codes
# Multiple mapping and none have viewer codes, keep one and delete rest
def choose_mappings_to_keep(grouped_mappings, viewer_table):
    keep_mapping_ids = set()
    delete_mapping_ids = set()

    for _, mappings in grouped_mappings.items():
        if len(mappings) == 1:
            keep_mapping_ids.add(mappings[0]["Id"])
            continue

        mappings_with_codes = []
        mappings_without_codes = []

        for mapping in mappings:
            codes = get_viewer_codes_for_mapping(viewer_table, mapping["Id"])
            if len(codes) > 0:
                mappings_with_codes.append(mapping)
            else:
                mappings_without_codes.append(mapping)

        if len(mappings_with_codes) == 1:
            keep_mapping_ids.add(mappings_with_codes[0]["Id"])
            for mapping in mappings_without_codes:
                delete_mapping_ids.add(mapping["Id"])

        elif len(mappings_with_codes) > 1:
            for mapping in mappings_with_codes:
                keep_mapping_ids.add(mapping["Id"])
            for mapping in mappings_without_codes:
                delete_mapping_ids.add(mapping["Id"])
            # if both/all have viewer codes, keep them all

        else:
            sorted_mappings = sorted(mappings, key=lambda m: m["Id"])
            keep_mapping_ids.add(sorted_mappings[0]["Id"])
            for mapping in sorted_mappings[1:]:
                delete_mapping_ids.add(mapping["Id"])

    return keep_mapping_ids, delete_mapping_ids


# The main function that builds the actual plan for one duplicate identity
# Choose primary user
# Load all mappings for all users in the identity group
# Group mappings by logic cal Actor Id and LPA
# Decide which mapping to keep/delete
# Decide which mapping moves to primary user
# Mark secodary user for deletion
def build_merge_plan_for_identity(identity, group, viewer_table, lpa_table):
    primary = determine_primary(group)
    secondaries = [u for u in group if u["Id"] != primary["Id"]]

    all_mappings = []
    for user in group:
        user_mappings = get_user_lpas(lpa_table, user["Id"])
        all_mappings.extend(user_mappings)

    grouped_mappings = group_mappings_by_logical_key(all_mappings)
    keep_mapping_ids, delete_mapping_ids = choose_mappings_to_keep(grouped_mappings, viewer_table)

    move_mapping_ids = []
    for mapping in all_mappings:
        if mapping["Id"] in keep_mapping_ids and mapping.get("UserId") != primary["Id"]:
            move_mapping_ids.append(mapping["Id"])

    compact_mappings = []
    for mapping in all_mappings:
        compact_mappings.append({
            "Id": mapping["Id"],
            "UserId": mapping.get("UserId"),
            "ActorId": clean(mapping.get("ActorId")),
            "LpaIdentifier": get_lpa_identifier(mapping),
        })

    return {
        "identity": identity,
        "primary_user_id": primary["Id"],
        "secondary_user_ids": [u["Id"] for u in secondaries],
        "all_mappings": compact_mappings,
        "keep_mapping_ids": sorted(list(keep_mapping_ids)),
        "move_mapping_ids": sorted(list(move_mapping_ids)),
        "delete_mapping_ids": sorted(list(delete_mapping_ids)),
        "delete_secondary_user_ids": [u["Id"] for u in secondaries],
    }

# runs with --execute option
# PLACEHOLDER
def execute_merge_plan(environment, merge_plan):
    print("\nExecuting merge plan...")

    actor_table = get_actor_users_table(environment)
    lpa_table = get_lpa_table(environment)

    for plan in merge_plan:
        print("\n" + "=" * 60)
        print(f"Executing identity: {plan['identity']}")
        print(f"Primary user: {plan['primary_user_id']}")

        primary_user_id = plan["primary_user_id"]

        # 1. Move mappings that should now belong to primary
        for mapping_id in plan["move_mapping_ids"]:
            mapping = get_mapping_by_id(lpa_table, mapping_id)

            if not mapping:
                print(f" - SKIP move {mapping_id}: mapping not found")
                continue

            current_user_id = mapping.get("UserId")

            if current_user_id == primary_user_id:
                print(f" - SKIP move {mapping_id}: already belongs to primary")
                continue

            print(
                f" - MOVE mapping {mapping_id}: "
                f"{current_user_id} -> {primary_user_id}"
            )

            lpa_table.update_item(
                Key={"Id": mapping_id},
                UpdateExpression="SET UserId = :new_user",
                ExpressionAttributeValues={
                    ":new_user": primary_user_id
                }
            )

        # 2. Delete duplicate mappings
        for mapping_id in plan["delete_mapping_ids"]:
            mapping = get_mapping_by_id(lpa_table, mapping_id)

            if not mapping:
                print(f" - SKIP delete mapping {mapping_id}: already deleted")
                continue

            print(f" - DELETE duplicate mapping {mapping_id}")

            lpa_table.delete_item(
                Key={"Id": mapping_id}
            )

        # 3. Delete secondary actor user accounts
        for secondary_user_id in plan["delete_secondary_user_ids"]:
            actor_user = get_actor_user_by_id(actor_table, secondary_user_id)

            if not actor_user:
                print(f" - SKIP delete user {secondary_user_id}: already deleted")
                continue

            print(f" - DELETE secondary account {secondary_user_id}")

            actor_table.delete_item(
                Key={"Id": secondary_user_id}
            )

    print("\nExecution complete")

#Export the merge plan to JSON before execution for review, migration artifact
def save_merge_plan(environment, merge_plan):

    timestamp = datetime.now(UTC).strftime("%Y%m%d_%H%M%S")
    filename = f"merge_plan_{environment}_{timestamp}.json"

    with open(filename, "w") as f:
        json.dump(merge_plan, f, indent=2)

    print(f"\nMerge plan saved to: {filename}")

# Helper to convert Dynamodb decimal to plain int
def clean(value):
    if isinstance(value, Decimal):
        return int(value)
    return value


# Prints the plan for a single duplicate identity group.
def print_merge_plan_for_identity(plan):
    print(f"Primary user: {plan['primary_user_id']}")
    print(f"Secondary users: {plan['secondary_user_ids']}")

    print("\nMappings:")
    for mapping in plan["all_mappings"]:
        mapping_id = mapping["Id"]
        actor_id = mapping.get("ActorId")
        lpa_id = mapping.get("LpaIdentifier")
        user_id = mapping.get("UserId")

        flags = []
        if mapping_id in plan["keep_mapping_ids"]:
            flags.append("KEEP")
        if mapping_id in plan["move_mapping_ids"]:
            flags.append("MOVE")
        if mapping_id in plan["delete_mapping_ids"]:
            flags.append("DELETE")

        print(
            f" - MappingId={mapping_id} | UserId={user_id} | "
            f"ActorId={actor_id} | LPA={lpa_id} | {'/'.join(flags) if flags else 'UNCHANGED'}"
        )

    print("\nPlanned actions:")
    for mapping in plan["all_mappings"]:
        mapping_id = mapping["Id"]
        actor_id = mapping.get("ActorId")
        lpa_id = mapping.get("LpaIdentifier")

        if mapping_id in plan["move_mapping_ids"]:
            print(f" - Move mapping {mapping_id} (ActorId {actor_id}, LPA {lpa_id}) to {plan['primary_user_id']}")

        if mapping_id in plan["delete_mapping_ids"]:
            print(f" - Delete duplicate mapping {mapping_id} (ActorId {actor_id}, LPA {lpa_id})")

    for secondary_user_id in plan["delete_secondary_user_ids"]:
        print(f" - Delete secondary account {secondary_user_id}")

#   It does:
#   connect to all required tables
#   scan all users
#   group users by identity
#   keep only duplicate identities
#   for each duplicate group:
#   build plan
#   print plan
#   collect plan into merge_plan
#   print a summary
#   save the merge plan JSON
#   optionally ask for execute confirmation
def run_plan(environment, limit=None, execute=False):
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

    print(f"Found {len(duplicates)} duplicate identities\n")

    processed = 0
    merge_plan = []

    for identity, group in duplicates.items():
        if limit and processed >= limit:
            break

        print("=" * 60)
        print(f"Identity: {identity}")

        plan = build_merge_plan_for_identity(identity, group, viewer_table, lpa_table)
        print_merge_plan_for_identity(plan)

        merge_plan.append(plan)
        processed += 1

    print("\n" + "=" * 60)
    print("MERGE PLAN SUMMARY")
    print("=" * 60)

    for plan in merge_plan:
        print(
            f"{plan['identity']} | "
            f"primary={plan['primary_user_id']} | "
            f"moves={len(plan['move_mapping_ids'])} | "
            f"deletes={len(plan['delete_mapping_ids'])} | "
            f"secondary_users={len(plan['delete_secondary_user_ids'])}"
        )

    save_merge_plan(environment, merge_plan)

    if execute:
        confirm = input("\nType YES to execute this merge plan: ")
        if confirm == "YES":
            execute_merge_plan(environment, merge_plan)
        else:
            print("Execution cancelled")


def main():
    args = parse_args()
    run_plan(args.environment, args.limit, args.execute)


if __name__ == "__main__":
    main()
