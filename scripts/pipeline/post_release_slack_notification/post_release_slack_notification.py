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

    def make_post_release_message(self):
        # title = "Use a Lasting Power of Attorney Production Release Successful"
        # user = "User: {}".format(
        #   os.getenv('CIRCLE_USERNAME', ""),
        # )
        # links = "view url: https://{0}/home \nuse url: https://{1}/home".format(
        #   self.config.public_facing_view_fqdn
        # )
        # commit_message = "Commit Message: \n"


        post_release_message = json.dumps({"text": "Test message by Andrew."})
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
                        default="./test_cluster_config.json",
                        help="Path to config file produced by terraform")
    parser.add_argument("--slack_webhook", type=str,
                        default=os.getenv('SLACK_WEBHOOK'),
                        help="Webhook to use, determines what channel to post to")

    args = parser.parse_args()

    work = PostReleaseNotifier(args.config_file_path)
    message = work.make_post_release_message()
    work.post_to_slack(args.slack_webhook, message)



if __name__ == "__main__":
    main()
