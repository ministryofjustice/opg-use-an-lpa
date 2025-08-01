# Managing Trivy scan findings in pull requests

## Summary

- minimse known critical and high severity CVEs in production
- reduce risk of threats to customer data and MoJ services
- analyse CVEs
- prioritise fix, defer fixing or acknowledge as not exploitable all high or critical findings

## Trivy image scanning

We use trivy to scan images for vulnerabilities in the image and operating system on pull request and path to live workflows in github actions.

This takes place during the [docker build job](https://github.com/ministryofjustice/opg-use-an-lpa/blob/main/.github/workflows/_build-and-push.yml) and before pushing the image to AWS ECR (elastic container repository)

If critical or high severity CVEs (common vulnerabilities and exposures) are found in the image or image operating system, the build job will fail.

This is to prevent CVEs making it to the production environment.

We should prioritise fixing the failed build, usually by upgrading the offending package or docker image layer in the docker file.

Where this isn't immediately possible we can manage the finding by *raising a high priority ticket for the team* and making an entry in the docker image's `.trivyignore.yaml` file

## Managing findings

[Trivy ignore files](https://trivy.dev/dev/docs/configuration/filtering/#suppression) allow us to suppress findings by the finding ID, for example the CVE reference.

Each `dockerfile` in this repository has a corresponding .trivyignore.

service-front/docker/app/.trivyignore.yaml

```yaml
vulnerabilities:

```

To suppress a finding add it as an ID in `vulnerabilities`;

```yaml
vulnerabilities:
  - id: CVE-2025-49794
  - id: CVE-2025-49795
  - id: CVE-2025-49796
```

We can suppress a finding until a date with `expired_at` and can provide reasoning fo the suppression with `statement`

```yaml
vulnerabilities:
  - id: CVE-2025-49794
    expired_at: 2025-08-30
    statement: No fixed version available at this time
  - id: CVE-2025-49795
  - id: CVE-2025-49796
```

Trivy supports a text format for ignore files (`.trivyignore`), but the docker build job expects the yaml format (`.trivyignore.yaml`)

Updates to the ignore file can then be included in your pull request to allow the workflow to progress past the docker build job.

*Suppressed findings are not uploaded to Github security.*

## Path to live

The path to live shares the same docker build job as the pull request workflow. It is therefore possible for a the path to live to fail with a CVE, for example if a vulnerability is reported between the last successful run of a pull request workflow and merging to main.

We should as a priority, fix or if not immediately possible, defer fixing with a ticket and suppression of the finding.
