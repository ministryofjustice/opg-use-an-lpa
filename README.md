# opg-use-my-lpa
OPG Use My LPA: Managed by opg-org-infra &amp; Terraform

[![CircleCI](https://circleci.com/gh/ministryofjustice/opg-use-my-lpa/tree/master.svg?style=svg)](https://circleci.com/gh/ministryofjustice/opg-use-my-lpa/tree/master)

## Setup

All commands assume a working directory of `opg-use-my-lpa`.

To bring up the local environment
```bash
docker-compose up
```

If you plan on developing the application you should also enable development mode.
```bash
docker-compose run viewer-composer composer development-enable
docker-compose exec viewer-app rm -f /tmp/config-cache.php
```

The Viewer service will be available via http://localhost:9001/

The API service will be available via http://localhost:9002/

### Tests

To run the unit tests
```bash
docker-compose run viewer-app /app/vendor/bin/phpunit
```

### Updating composer dependencies

Composer install is run when the app container is built, and on a standard `docker-compose up`.

It can also be run independently with:
```bash
docker-compose run viewer-composer
```

New packages can be added with:
```bash
docker-compose run viewer-composer composer require author/package
```

Packages can be removed with:
```bash
docker-compose run viewer-composer composer remove author/package
```
