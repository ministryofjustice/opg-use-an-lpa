import boto3
from botocore.config import Config
from datetime import datetime
import logging
import json
import os


class Redactor:
    local_directory = 's3_upload'
    remote_directory = datetime.today().strftime('%Y%m%d')

    def __init__(self, environment, delete_log_streams, search_string):
        self.log_group = 'example_application_logs'
        self.s3_bucket = 'opg-use-an-lpa-redacted-logs-{environment}-eu-west-1'.format(
            environment=environment)
        config = Config(
            region_name='eu-west-1',
        )
        self.log_client = boto3.client('logs', config=config)
        self.s3_client = boto3.client('s3', config=config)
        self.delete_log_streams = delete_log_streams
        self.search_string = search_string

        if not os.path.exists(self.local_directory):
            os.makedirs(self.local_directory)

    def load_log_streams(self):
        logging.info('Loading LogStreams from CSV')
        with open('logStreams.csv', newline='') as file:
            return file.read().splitlines()

    def get_local_path(self, log_stream):
        return self.local_directory + '/' + self.get_file_name(log_stream)

    def get_file_name(self, log_stream):
        return log_stream.replace('/', '-')

    def redact_stream_to_file(self, log_stream):
        logging.info('Processing: {log_stream}'.format(log_stream=log_stream))
        with open(self.get_local_path(log_stream), 'a') as log_file:
            try:
                block = self.log_client.get_log_events(
                    logGroupName=self.log_group,
                    logStreamName=log_stream,
                    startFromHead=True
                )
                while len(block['events']) > 0:
                    logging.debug('Events in LogStream Block: {length}'.format(
                        length=len(block['events'])))
                    logging.debug('Next Forward Token: {token}'.format(
                        token=block['nextForwardToken']))
                    logging.info('Processing LogStream Events')
                    for event in block['events']:
                        if self.search_string not in event['message']:
                            json.dump(event, log_file)
                            log_file.write('\n')
                    logging.debug('Getting Next Block')
                    block = self.log_client.get_log_events(
                        logGroupName=self.log_group,
                        logStreamName=log_stream,
                        startFromHead=True,
                        nextToken=block['nextForwardToken']
                    )
                logging.info('Finished Processing: {log_stream}'.format(
                    log_stream=log_stream))
                return True
            except self.log_client.exceptions.ResourceNotFoundException:
                logging.warning('LogStream Not Found: {log_stream}'.format(
                    log_stream=log_stream))
                return False

    def upload_to_s3(self, log_stream):
        local_file = self.get_local_path(log_stream)
        s3_path = '{remote_directory}/{file_name}'.format(
            s3_bucket=self.s3_bucket,
            remote_directory=self.remote_directory,
            file_name=self.get_file_name(log_stream))
        logging.info('Uploading {local_file} to {s3_bucket}/{s3_path}'.format(
            local_file=local_file,
            s3_bucket=self.s3_bucket,
            s3_path=s3_path)
        )
        try:
            with open(local_file, 'rb') as file:
                self.s3_client.put_object(
                    ACL='private',
                    Body=file,
                    Bucket=self.s3_bucket,
                    Key=s3_path,
                    ServerSideEncryption='AES256'
                )
        except Exception as error:
            logging.error(error)
            logging.error('S3 Upload failed for: {local_file}'.format(
                local_file=local_file))
            raise Exception('Upload to S3 Failed - Exiting')

    def delete_temp_file(self, log_stream):
        local_file = self.get_local_path(log_stream)
        logging.info('Deleting Local File: {local_file}'.format(
            local_file=local_file))
        if os.path.exists(local_file):
            os.remove(local_file)
            logging.info('Deleted: {local_file}'.format(local_file=local_file))
        else:
            logging.warning('Cannot find file to delete: {local_file}'.format(
                local_file=local_file))

    def delete_log_stream(self, log_stream):
        if self.delete_log_streams:
            try:
                logging.warning('Deleting LogStream: {log_stream}'.format(log_stream=log_stream))
                self.log_client.delete_log_stream(
                    logGroupName=self.log_group,
                    logStreamName=log_stream
                )
            except self.log_client.exceptions.ResourceNotFoundException:
                logging.warning('LogStream Not Found: {log_stream}'.format(
                    log_stream=log_stream))
        else:
            logging.info(
                'LogStream Deletion Disabled - Would have tried to delete: {log_stream}'.format(log_stream=log_stream))

    def redact(self):
        logging.info('Processing LogStreams')
        for log_stream in self.load_log_streams():
            if self.redact_stream_to_file(log_stream):
                self.upload_to_s3(log_stream)
                self.delete_temp_file(log_stream)
                self.delete_log_stream(log_stream)
            else:
                logging.warning('LogStream Not Processed: {log_stream}'.format(
                    log_stream=log_stream))
        logging.info('Redaction of LogStreams Complete.')


def main():
    logging.basicConfig(
        format='%(asctime)s %(levelname)-8s %(message)s',
        level=logging.INFO,
        datefmt='%Y-%m-%d %H:%M:%S')
    logging.info('Starting...')
    environment = os.environ.get('ENVIRONMENT', 'development')
    delete_log_streams = os.getenv(
        'DELETE_LOGSTREAMS', 'False').lower() in ('true', '1', 't')
    logging.info('Running against {environment}'.format(
        environment=environment))
    search_string = 'example log string'
    redactor = Redactor(environment, delete_log_streams, search_string)
    redactor.redact()


if __name__ == '__main__':
    main()
