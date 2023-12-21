#OWASP ZAP container

Can be run against actor in local environment with:

```bash
make up_all
make security_test
```

Reports in sarif and markdown are generated in the zap/reports folder.

The zap container takes three environment variables:

ULPA_USER: signin user email
ULPA_PWORD: their password
ULPA_URI: the url to hit, i.e. https://proxy:9042/

which are injected into the zap automation config yml file.