# opg-use-my-lpa

OPG Use My LPA: Managed by opg-org-infra &amp; Terraform

[![CircleCI](https://circleci.com/gh/ministryofjustice/opg-use-an-lpa.svg?style=svg)](https://circleci.com/gh/ministryofjustice/opg-use-an-lpa)
[![Coverage Status](https://coveralls.io/repos/github/ministryofjustice/opg-use-an-lpa/badge.svg?branch=master)](https://coveralls.io/github/ministryofjustice/opg-use-an-lpa?branch=master)
[![codecov coverage status](https://codecov.io/gh/ministryofjustice/opg-use-an-lpa/branch/master/graph/badge.svg)](https://codecov.io/gh/ministryofjustice/opg-use-an-lpa)
## Setup

Clone the following two repositories into the same base directory:

- https://github.com/ministryofjustice/opg-use-an-lpa
- https://github.com/ministryofjustice/opg-sirius-api-gateway

All commands assume a working directory of `opg-use-my-lpa`.

To bring up the local environment

```bash
docker-compose -f docker-compose.yml \
-f ../opg-sirius-api-gateway/docker-compose.yml \
-f ../opg-sirius-api-gateway/docker-compose-integration.yml up

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

To run the unit tests (the command for viewer-app and actor-app will run exactly the same suite of unit tests in the front service)

```bash
docker-compose run viewer-app /app/vendor/bin/phpunit
docker-compose run actor-app /app/vendor/bin/phpunit

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
.
