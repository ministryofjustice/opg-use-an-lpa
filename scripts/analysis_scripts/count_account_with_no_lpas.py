import argparse
import json


def count_by_maps(actors_json_path, maps_json_path):
    with open(maps_json_path) as f:
        maps_data = json.load(f)

        with open(actors_json_path) as f:
            actors_data = json.load(f)

            list_of_userids = []
            indicator = 0

            actors = []
            for value in actors_data['Items']:
                actors.append(value['Id']['S'])

            maps = []
            for value in maps_data['Items']:
                maps.append(value['UserId']['S'])

            for actor_id in actors_data['Items']:
                if actor_id['Id']['S'] not in maps_data['Items']:
                    list_of_userids.append(actor_id['Id']['S'])
                indicator += 1
                if indicator % 10 == 0:
                    print("Maps processed: ", indicator)
            print(list_of_userids)
    print("Maps processed: ", indicator)

def main():
    arguments = argparse.ArgumentParser(
        description="Compare JSON files to count user accounts with 0 LPAs added.")
    arguments.add_argument("--actors_json_path",
                        default="./actorusers_ids_only.json",
                        help="Path to ActorUsers JSON file")
    arguments.add_argument("--maps_json_path",
                        default="./actormap_ids_only.json",
                        help="Path to UserLpaActorMap JSON file")

    args = arguments.parse_args()
    count_by_maps(args.actors_json_path, args.maps_json_path)


if __name__ == "__main__":
    main()
