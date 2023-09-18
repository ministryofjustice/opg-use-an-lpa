import argparse
import json
import os
from jinja2 import Template
import requests
import math
import sys

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
        # can only use 50 blocks
        max_blocks = 50
        max_chars = 3000
        content = ""
        # title = 'Service Statistics - Use a Lasting Power of Attorney Production'
        title = 'TEST STATS'
        colour = '#9933ff'
        with open(template_path, 'r') as file:
            template_str = file.read()
        
        template = Template(template_str)

        with open(stats_path, 'r') as file:
            stats_content = file.read()
        stats = json.loads(stats_content)["statistics"]

        message = {
            'attachments': [
                {
                    'title': title,
                    'footer': '',
                },
            ]
        }
        

        # need ot push more info into one block
        # due to the cap
        l = len(stats) + 1 
        per = math.ceil(l/max_blocks)
        
        for event, details in stats.items():
            mapping = {
                'event': event,
                'total': details['total'],
                'monthly': details['monthly'],
            }
            message["attachments"].append( {'text': template.render(**mapping), 'footer': "", 'title':'', "color": colour})
        
        content = json.dumps(message)
        
        return content


def main():
    parser = argparse.ArgumentParser(
        description='Service Stats Slack notification.')

    parser.add_argument('--slack_webhook', type=str,
                        help='Webhook to use, determines what channel to post to')
    parser.add_argument('--stats_path', type=str,
                        default='',
                        help='Path to file containing stats')
    parser.add_argument('--template_path', type=str,
                        help='Path to the template file to use for a slack notification')
    parser.add_argument('--test', dest='test_mode', action='store_const',
                        const=True, default=False,
                        help='Generate message bot do not post to slack')
    parser.add_argument('--message_path', type=str, default="",
                        help='Path to a pre-done message file to use for a slack notification')

    args = parser.parse_args()

    if len(args.message_path) > 0:
        with open(args.message_path, 'r') as file:
            message = file.read()
    else:
        work = MessageGenerator()
        message = work.generate_text_message(args.stats_path, args.template_path)
    
    if args.test_mode:
        print(message)
    else:
        post_to_slack(args.slack_webhook, message)


if __name__ == '__main__':
    main()
