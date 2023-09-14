import argparse
import json
import os
from jinja2 import Template
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
  
    def generate_text_message(self, stats_path, template_path):
        with open(template_path, 'r') as file:
            template_str = file.read()

        with open(stats_path, 'r') as file:
            stats = file.read()

        mapping = {
            'stats': stats or 'Stats not provided'
        }
        message = Template(template_str)
        text_message = {
            'text': message.render(**mapping)
        }
        content = json.dumps(text_message)
        return content


def main():
    parser = argparse.ArgumentParser(
        description='Service Stats Slack notification.')

    parser.add_argument('--slack_webhook', type=str,
                        default=os.getenv('SLACK_WEBHOOK'),
                        help='Webhook to use, determines what channel to post to')
    parser.add_argument('--stats_path', type=str,
                        default='',
                        help='Path to file containing stats')
    parser.add_argument('--template_path', type=str,
                        help='Path to the template file to use for a slack notification')
    parser.add_argument('--test', dest='test_mode', action='store_const',
                        const=True, default=True,
                        help='Generate message bot do not post to slack')

    args = parser.parse_args()

    work = MessageGenerator()

    message = work.generate_text_message(args.stats_path, args.template_path)
    print(message)

    if not args.test_mode:
        post_to_slack(args.slack_webhook, message)


if __name__ == '__main__':
    main()
