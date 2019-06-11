# opg-use-my-lpa
OPG Use My LPA: Managed by opg-org-infra &amp; Terraform

[![CircleCI](https://circleci.com/gh/ministryofjustice/opg-use-an-lpa.svg?style=svg)](https://circleci.com/gh/ministryofjustice/opg-use-an-lpa)

## Setup

All commands assume a working directory of `opg-use-my-lpa`.

To bring up the local environment
```bash
docker-compose up
```

If you plan on developing the application you should also enable development mode.
```bash
docker-compose run front-composer composer development-enable
docker-compose exec viewer-app rm -f /tmp/config-cache.php

docker-compose run api-composer composer development-enable
docker-compose exec api-app rm -f /tmp/config-cache.php
```

The Viewer service will be available via http://localhost:9001

The Actor service will be available via http://localhost:9002

The API service will be available via http://localhost:9003

### Tests

To run the unit tests
```bash
docker-compose run viewer-app /app/vendor/bin/phpunit

docker-compose run api-app /app/vendor/bin/phpunit
```

### Functional (Behave) test

To run the Behave functional tests

```bash
docker-compose run feature-tests
```

To run a tagged subset of tests
```bash
docker-compose run feature-tests behave --tags=<TAG_NAME>
```

### Updating composer dependencies

Composer install is run when the app container is built, and on a standard `docker-compose up`.

It can also be run independently with:
```bash
docker-compose run api-composer

docker-compose run front-composer
```

New packages can be added with:
```bash
docker-compose run api-composer composer require author/package

docker-compose run front-composer composer require author/package
```

Packages can be removed with:
```bash
docker-compose run api-composer composer remove author/package

docker-compose run front-composer composer remove author/package
```
