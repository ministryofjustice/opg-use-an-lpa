import boto3
import click
import time
from datetime import datetime


class LogExtractor:
    account = {
        "development": "367815980639",
        "preproduction": "888228022356",
        "production": "690083044361"
    }
    region = ""
    wait_time = 0

    def __init__(self, environment, region, log_group):
        self.environment = environment
        self.region = region
        self.wait_time = 0
        self.log_group = log_group
        self.start_time = int(datetime.now().timestamp())
        self.client_logs = boto3.client('logs', region_name=self.region)

    def print_from_logs(self, start_time, finish_time):
        query = """
            fields @timestamp
            | filter @message like 'UserService->authenticate('
            | parse 'authenticate(*)' as userPass
            | sort @timestamp desc
            | limit 10000
        """

        start = datetime.strptime(start_time, "%Y/%m/%d %H:%M:%S")
        finish = datetime.strptime(finish_time, "%Y/%m/%d %H:%M:%S")

        log_records = []
        log_csv = open("logs.csv", "w")
        start_query_response = self.client_logs.start_query(
            logGroupName=self.log_group,
            startTime=int(start.timestamp()),
            endTime=int(finish.timestamp()),
            queryString=query,
        )
        query_id = start_query_response["queryId"]
        time.sleep(45) # Needs a while to collect the results

        response = self.client_logs.get_query_results(
            queryId=query_id
        )

        results_gathered = len(response["results"])
        print(f"Gathered {results_gathered} records from this set")

        if results_gathered > 10000:
            print(
                f"WARNING -- Too many results in this time frame - Consider using chunking"
            )

        for fields in response["results"]:
            log_record = {
                "timestamp": fields[0]["value"],
                "userPass": fields[1]["value"],
            }
            log_records.append(log_record)

        for record in log_records:
            line = f"{record['timestamp']},{record['userPass']}\n"
            log_csv.write(str(line))
        log_csv.close()


@click.command()
@click.option("-l", "--log_group", default="development_application_logs")
@click.option("-e", "--environment", default="development")
@click.option("-s", "--start_time", default="2022/08/19 00:00:00")
@click.option("-f", "--finish_time", default="2022/08/25 23:00:00")
def main(log_group, environment, start_time, finish_time):
    region = "eu-west-1"
    log_extractor = LogExtractor(environment, region, log_group)
    log_extractor.print_from_logs(start_time, finish_time)


"""
The log parser allows you to select logs from a single log group between certain datetimes with a 
certain insights query.
"""
if __name__ == "__main__":
    main()
