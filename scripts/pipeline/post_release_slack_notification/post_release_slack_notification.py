import argparse
import requests
import json
import os


class PostReleaseNotifier:
    config = ''

    def __init__(self, config_file):
        self.config = self.read_parameters_from_file(config_file)

    def read_parameters_from_file(self, config_file):
        with open(config_file) as json_file:
            config = json.load(json_file)
            return config

    def make_post_release_message(self, commit_message):
        block_kit_message = {
          # "text": ":star: Use a Lasting Power of Attorney Production Release Successful :star:",
          "blocks": [
            {
              "type": "header",
              "text": {
                "type": "plain_text",
                "text": ":star: Use a Lasting Power of Attorney Production Release Successful :star:"
              }
            },
            {
              "type": "context",
              "elements": [
                {
                  "text": str(os.getenv('CIRCLE_USERNAME', "circleci username")),
                  "type": "mrkdwn"
                }
              ]
            },
            {
              "type": "divider"
            },
            {
              "type": "section",
              "text": {
                "type": "mrkdwn",
                "text": "*links* "
              }
            },
            {
              "type": "section",
              "text": {
                "type": "mrkdwn",
                "text": "*use* https://{}/home".format(
                  self.config['public_facing_use_fqdn']
                )
              }
            },
            {
              "type": "section",
              "text": {
                "type": "mrkdwn",
                "text": "*view* https://{}/home".format(
                  self.config['public_facing_view_fqdn']
                )
              }
            },
            {
              "type": "divider"
            },
            {
              "type": "section",
              "fields": [
                {
                  "type": "mrkdwn",
                  "text": commit_message
                }
              ]
            }
          ]
        }

        post_release_message = json.dumps(block_kit_message)
        return post_release_message


    def post_to_slack(self, slack_webhook, message):

            response = requests.post(
                slack_webhook, data=message,
                headers={'Content-Type': 'application/json'}
            )
            if response.status_code != 200:
                raise ValueError(
                    'Request to slack returned an error %s, the response is:\n%s'
                    % (response.status_code, response.text)
                )
            else:
                print(response)

def main():
    parser = argparse.ArgumentParser(
        description="Add or remove your host's IP address to the viewer and actor loadbalancer ingress rules.")

    parser.add_argument("--config_file_path", type=str,
                        default="./cluster_config.json",
                        help="Path to config file produced by terraform")
    parser.add_argument("--slack_webhook", type=str,
                        default=os.getenv('SLACK_WEBHOOK'),
                        help="Webhook to use, determines what channel to post to")
    parser.add_argument("--commit_message", type=str,
                        default="",
                        help="Commit message to include in slack notification")

    args = parser.parse_args()

    work = PostReleaseNotifier(args.config_file_path)
    message = work.make_post_release_message(args.commit_message)
    print(message)
    # work.post_to_slack(args.slack_webhook, message)



if __name__ == "__main__":
    main()
