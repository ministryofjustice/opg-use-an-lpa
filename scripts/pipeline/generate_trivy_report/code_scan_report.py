import argparse
import json
import os
import requests
import sys

class CodeScanReport:
    def __init__(self, repo, github_token) -> None:
        self.url = f'https://api.github.com/repos/{repo}/code-scanning/alerts'

        self.headers = {
            'Authorization': f'Bearer {github_token}',
            'Accept': "Accept: application/vnd.github+json"
        }

        self.params = {
            'per_page': 100,
            'page': 1
        }


    def _get_all_alerts(self) -> list:
        """Function that gets all alerts from the code scanning api in GitHub"""
        all_alerts = []
        while True:
            response = requests.get(self.url, headers=self.headers, params=self.params)

            if response.status_code == 200:
                alerts = response.json()
                all_alerts.extend(alerts)

                if 'next' in response.links:
                    self.params['page'] += 1
                else:
                    break
        return all_alerts


    def _get_open_alerts(self) -> list:
        """Function that gets all currently open alerts"""
        all_alerts = self._get_all_alerts()
        open_alerts = []

        for alert in all_alerts:
            if alert['state'] == 'open':
                open_alerts.append(alert)

        return open_alerts


    def get_trivy_alerts(self) -> list:
        """Function retruns high and critical severity trivy alerts"""
        open_alerts = self._get_open_alerts()

        high_alerts = []
        critical_alerts = []

        for alert in open_alerts:
            tool_name = alert['tool']['name']
            if tool_name != 'Trivy':
                continue

            alert_severity = alert['rule']['security_severity_level']

            if alert_severity == 'high':
                high_alerts.append(alert)
            elif alert_severity == 'critical':
                critical_alerts.append(alert)

        return high_alerts, critical_alerts


    def generate_report(self) -> str:
        high_alerts, critical_alerts = self.get_trivy_alerts()

        overall_report = (
            f"Trivy Code Scan Report\n"
            f"{'-' * 100}\n"
            f"*HIGH ALERTS*: [{len(high_alerts)}]\n"
            f"*CRITICAL ALERTS*: [{len(critical_alerts)}]\n"
            f"{'-' * 100}\n"
        )

        critical_alert_report = ''
        for alert in critical_alerts:
                critical_alert_report += f"{'-' * 100}\n"
                critical_alert_report += f"*URL*: {alert['html_url']}\n"
                critical_alert_report += f"*Description*: {alert['rule']['description']}\n"
                critical_alert_report += f"*CVE*: {alert['rule']['id']}\n"
                critical_alert_report += f"*Created at*: {alert['created_at']}\n"
                critical_alert_report += f"*Severity*: {alert['rule']['tags'][0]}\n"
                critical_alert_report += f"{'-' * 100}\n"

        high_alert_report = ''
        for alert in high_alerts:
                high_alert_report += f"{'-' * 100}\n"
                high_alert_report += f"*URL*: {alert['html_url']}\n"
                high_alert_report += f"*Description*: {alert['rule']['description']}\n"
                high_alert_report += f"*CVE*: {alert['rule']['id']}\n"
                high_alert_report += f"*Created at*: {alert['created_at']}\n"
                high_alert_report += f"*Severity*: {alert['rule']['tags'][0]}\n"
                high_alert_report += f"{'-' * 100}\n"

        return overall_report, critical_alert_report, high_alert_report

    def post_to_slack(self, slack_webhook, report):
        """Function to post vulnrability report to slack"""
        post_data = json.dumps({'text': report})
        response = requests.post(
            slack_webhook, data=post_data,
            headers={'Content-Type': 'application/json'}
        )
        if response.status_code != 200:
            raise ValueError(
                f'Request to slack returned an error {response.status_code},'
                f'the response is:\n'
                f'{response.text}'
            )


def main():
    parser = argparse.ArgumentParser(
    description='Check Trivy code scan alerts.')
    parser.add_argument('--slack_webhook',
                        default=os.getenv('SLACK_WEBHOOK'),
                        help='Webhook to use, determines what channel to post to')

    args = parser.parse_args()

    github_token = os.getenv('GITHUB_TOKEN')
    repo = 'ministryofjustice/opg-use-an-lpa'

    vulnrability_report = CodeScanReport(repo, github_token)

    overall_report, critical_alert_report, high_alert_report = vulnrability_report.generate_report()

    if len(critical_alert_report) == 0 and len(high_alert_report) == 0:
        print("No Critical or High alerts, Quitting")
        sys.exit(0)

    slack_report = f"""
"""

    print(f"{overall_report}")
    slack_report += f"{overall_report}\n"

    if critical_alert_report != '':
        print(f"{critical_alert_report}")
        slack_report += f"{critical_alert_report}\n"

    if high_alert_report != '':
        print(f"{high_alert_report}")
        slack_report += f"{high_alert_report}\n"


    vulnrability_report.post_to_slack(
            args.slack_webhook,
            slack_report,
        )


if __name__ == "__main__":
    main()
