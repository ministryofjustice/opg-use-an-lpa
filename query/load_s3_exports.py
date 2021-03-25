import gzip
import json_lines
import os
import pandas as pd


def load_export_dataframe(gz_path):
    dynamodb_json = []

    for entry in os.scandir(gz_path):
        if (entry.path.endswith(".gz")):
            with json_lines.open(entry.path) as f:
                for item in f:
                    dynamodb_json.append(item['Item'])
    dataframe = pd.DataFrame(dynamodb_json)
    return dataframe

exports = {
  'ActorCodes':'demo-ActorCodes/AWSDynamoDB/01616672948566-2bd6cda2/data/',
  'ActorUsers':'demo-ActorUsers/AWSDynamoDB/01616672948854-f7585baa/data/',
  'ViewerCodes':'demo-ViewerCodes/AWSDynamoDB/01616672949229-89913a34/data/',
  'ViewerActivity':'demo-ViewerActivity/AWSDynamoDB/01616672949494-747f6af2/data/',
  'UserLpaActorMap':'demo-UserLpaActorMap/AWSDynamoDB/01616672949780-0dfc333e/data/'
}

userlpaactormap_df = load_export_dataframe(
      './s3_objects/{}'.format(
        exports.get('UserLpaActorMap')
        )
      )

print('\nList LPA Maps:')
print(userlpaactormap_df)
print('\nCount Accounts with LPAs:')
print(userlpaactormap_df["ActorId"].value_counts())
