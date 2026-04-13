# Managing Snyk scan findings in pull requests

## Summary

- Minimise known critical and high severity CVEs in production
- Reduce risk to customer data and MoJ services
- Review and understand vulnerabilities
- Prioritise fixing, deferring, or accepting risk for all high/critical findings

## Snyk image scanning

We use Snyk to scan container images for vulnerabilities during pull request and path-to-live workflows in GitHub Actions.

This takes place during the [docker build job](https://github.com/ministryofjustice/opg-use-an-lpa/blob/main/.github/workflows/_build-and-push.yml) before pushing the image to ECR.

If critical or high severity CVEs are found, the build job will fail and results are uploaded to the Github Security tab. This is to prevent CVEs making it to production.

We should prioritise fixing the failed build, usually by upgrading the offending package or docker image layer in the docker file.

Where this isn't immediately possible we can manage the finding by *raising a high priority ticket for the team* and making an entry in the docker image's `.snyk` file.

## Managing findings

[.snyk files](https://docs.snyk.io/developer-tools/snyk-cli/commands/ignore) allow us to suppress findings by the Snyk vulnerability ID (not the CVE directly) until an expiry date:
```yml
ignore:
  '<ISSUE_ID>':
    - '*':
        reason: <REASON>
        expires: <EXPIRY>
```
Each `Dockerfile` in this repository has a corresponding `.snyk` file.

To add a suppression there are 2 options:

### Option 1 - Use [Snyk CLI](https://docs.snyk.io/manage-risk/policies/the-.snyk-file#use-the-snyk-cli-and-the-.snyk-file-for-snyk-open-source)
Requires Snyk CLI and authentication.

Install with Brew:
```bash
brew tap snyk/tap
brew install snyk
```
Authenticate:
```
snyk auth
```

Optionally run a scan on a container to get vulnerability IDs:
```bash
snyk container test opg-use-an-lpa-api-app:latest --severity-threshold=high
```

Navigate to the folder containing the `.snyk` file and run `snyk ignore`:

```bash
snyk ignore --id=SNYK-PHP-FIREBASEPHPJWT-11356865 --expiry=2026-04-23 --reason="No fix available at this time"
```
If `--expiry` is omitted Snyk adds a 30 day expiry by default.
### Option 2 - Manually edit .snyk
```yml
ignore:
  SNYK-PHP-FIREBASEPHPJWT-11356865:
    - '*':
        reason: No fixed version available at this time
        expires: 2026-04-23T00:00:00.000Z
```

Updates to the ignore file can then be included in your pull request to allow the workflow to progress past the docker build job.

*Suppressed findings are not uploaded to Github security.*
