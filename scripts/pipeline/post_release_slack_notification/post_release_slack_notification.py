import argparse
import json
import os
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

    def generate_text_message(self, commit_message):
        title = ":star: Use a Lasting Power of Attorney Production Release Successful :star:"
        username = "user: {0}".format(
            str(os.getenv(
                'CIRCLE_USERNAME', "circleci username")))
        links = "*links:* \n*use:* https://{0}/home \n*view:* https://{1}/home \n*circleci build url:* {2}".format(
            self.config['public_facing_use_fqdn'],
            self.config['public_facing_view_fqdn'],
            str(os.getenv(
                'CIRCLE_BUILD_URL', "build url no included"))
          )
        text_message = {
            "text":"{0}\n\n{1}\n\n{2}\n\n{3}\n".format(
                title,
                username,
                links,
                commit_message
            ),
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

    args = parser.parse_args()

    work = MessageGenerator(args.config_file_path)

    message = work.generate_text_message(args.commit_message)
    print(message)

    post_to_slack(args.slack_webhook, message)



if __name__ == "__main__":
    main()
