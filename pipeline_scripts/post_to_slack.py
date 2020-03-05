from urllib import request, parse
import json
import argparse
import os


class SlackPoster:
    slack_webhook = ''

    def __init__(self, webhook):
        self.slack_webhook = webhook

    def send_message_to_slack(self, title, message, link):
        post = {
            "text": "{0}".format(message),
            "blocks": [
                {
                    "type": "section",
                    "text": {
                        "type": "mrkdwn",
                        "text": "{0}".format(title)
                    }
                },
                {
                    "type": "section",
                    "block_id": "section567",
                    "text": {
                        "type": "mrkdwn",
                        "text": "{0}".format(link)
                    }
                }
            ]
        }
        try:
            json_data = json.dumps(post)
            req = request.Request(self.slack_webhook,
                                  data=json_data.encode('ascii'),
                                  headers={'Content-Type': 'application/json'})
            resp = request.urlopen(req)
        except Exception as em:
            print("EXCEPTION: " + str(em))


def main():
    parser = argparse.ArgumentParser(
        description="Posts a message to slack.")

    parser.add_argument("--message",
                        help="Message to post to slack")
    parser.add_argument("--title",
                        help="Message to post to slack")
    parser.add_argument("--job_link",
                        help="Message to post to slack")
    parser.add_argument("--slack_webhook",
                        default=os.getenv('SLACK_WEBHOOK'),
                        help="Webhook to use, determines what channel to post to")

    args = parser.parse_args()
    work = SlackPoster(args.slack_webhook)
    work.send_message_to_slack(
        args.title, args.message, args.job_link)


if __name__ == "__main__":
    main()
