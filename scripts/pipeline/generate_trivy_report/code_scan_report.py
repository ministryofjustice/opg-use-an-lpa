import requests


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
            f"{'-' * 100}\n"
            f"HIGH ALERTS [{len(high_alerts)}]\n"
            f"CRITICAL ALERTS [{len(critical_alerts)}]\n"
            f"{'-' * 100}\n"
        )

        critical_alert_report = ''
        for alert in critical_alerts:
                critical_alert_report += f"{'-' * 100}\n"
                critical_alert_report += f"URL: {alert['html_url']}\n"
                critical_alert_report += f"CVE: {alert['rule']['id']}\n"
                critical_alert_report += f"Created at: {alert['created_at']}\n"
                critical_alert_report += f"Severity: \033[1;33;40m{alert['rule']['tags'][0]}\033[1;0;40m\n"
                critical_alert_report += f"{'-' * 100}\n"

        high_alert_report = ''
        for alert in high_alerts:
                high_alert_report += f"{'-' * 100}\n"
                high_alert_report += f"URL: {alert['html_url']}\n"
                high_alert_report += f"CVE: {alert['rule']['id']}\n"
                high_alert_report += f"Created at: {alert['created_at']}\n"
                high_alert_report += f"Severity: \033[1;33;40m{alert['rule']['tags'][0]}\033[1;0;40m\n"
                high_alert_report += f"{'-' * 100}\n"

        return overall_report, critical_alert_report, high_alert_report


def main():
    print(f"Stating script")

    github_token = ''
    repo = 'ministryofjustice/opg-use-an-lpa'

    vulnrability_report = CodeScanReport(repo, github_token)

    overall_report, critical_alert_report, high_alert_report = vulnrability_report.generate_report()

    print(f"{overall_report}")

    if critical_alert_report != '':
        print(f"{critical_alert_report}")

    if high_alert_report != '':
        print(f"{high_alert_report}")


if __name__ == "__main__":
    main()
