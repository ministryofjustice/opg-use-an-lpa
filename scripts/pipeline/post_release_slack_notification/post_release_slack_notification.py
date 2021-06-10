import argparse
import json
import os
from string import Template
import requests


def post_to_slack(slack_webhook, message):

    response = requests.post(
        slack_webhook, data=message,
        headers={'Content-Type': 'application/json'}
    )
    if response.status_code != 200:
        raise ValueError(
            'Request to slack returned an error %s, the response is:\n%s'
            % (response.status_code, response.text)
            )
class MessageGenerator:
    config = ''

    def __init__(self, config_file):
        self.config = self.read_parameters_from_file(config_file)

    @staticmethod
    def read_parameters_from_file(config_file):
        with open(config_file) as json_file:
            config = json.load(json_file)
            return config

    def generate_text_message(self, commit_message, template_path):
        with open(template_path, 'r') as file:
            template_str = file.read()

        mapping = {
          'user': str(os.getenv('CIRCLE_USERNAME', "circleci username")),
          'use_url':'https://{}/home'.format(
            self.config['public_facing_use_fqdn']) or 'Use URL not provided',
          'view_url': 'https://{}/home'.format(
            self.config['public_facing_view_fqdn']) or 'View URL not provided',
          'admin_url': 'Admin URL not provided',
          'circleci_build_url': str(os.getenv('CIRCLE_BUILD_URL', 'Build url not included')),
          'commit_message': commit_message or 'Commit message not provided'
          }

        message = Template(template_str)

        text_message = {
            "text":message.substitute(**mapping)
        }

        post_release_message = json.dumps(text_message)
        return post_release_message


def main():
    parser = argparse.ArgumentParser(
        description="Post-release Slack notifications.")

    parser.add_argument("--config_file_path", type=str,
                        default="/tmp/cluster_config.json",
                        help="Path to config file produced by terraform")
    parser.add_argument("--slack_webhook", type=str,
                        default=os.getenv('SLACK_WEBHOOK'),
                        help="Webhook to use, determines what channel to post to")
    parser.add_argument("--commit_message", type=str,
                        default="",
                        help="Commit message to include in slack notification")
    parser.add_argument("--template_path", type=str,
                        help="Path to the template file to use for a slack notification")

    args = parser.parse_args()

    work = MessageGenerator(args.config_file_path)

    message = work.generate_text_message(args.commit_message, args.template_path)
    print(message)

    post_to_slack(args.slack_webhook, message)



if __name__ == "__main__":
    main()
