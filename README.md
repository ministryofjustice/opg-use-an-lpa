# opg-use-my-lpa

OPG Use My LPA: Managed by opg-org-infra &amp; Terraform

[![CircleCI](https://circleci.com/gh/ministryofjustice/opg-use-an-lpa.svg?style=svg)](https://circleci.com/gh/ministryofjustice/opg-use-an-lpa)
[![Coverage Status](https://coveralls.io/repos/github/ministryofjustice/opg-use-an-lpa/badge.svg?branch=master)](https://coveralls.io/github/ministryofjustice/opg-use-an-lpa?branch=master)
[![codecov coverage status](https://codecov.io/gh/ministryofjustice/opg-use-an-lpa/branch/master/graph/badge.svg)](https://codecov.io/gh/ministryofjustice/opg-use-an-lpa)
## Setup

Clone the following two repositories into the same base directory:

- https://github.com/ministryofjustice/opg-use-an-lpa
- https://github.com/ministryofjustice/opg-sirius-api-gateway
- https://github.com/ministryofjustice/opg-data-lpa-codes

All commands assume a working directory of `opg-use-my-lpa`.

In all cases commands are run with a docker-compose command prefix

```shell
# This should replace all instances of <DOCKER_COMPOSE> in commands given below
docker-compose -f docker-compose.yml -f docker-compose.dependencies.yml
```

Build docker-compose files with no cache option.
Run this regularly to keep base docker images up to date,
as these include potential security fixes.

```shell
<DOCKER_COMPOSE> build --no-cache
```

You can also use the `--no-cache` option on other docker-compose commands,
to clear out previously cached images.

***Note:*** this can take several minutes to run.

To bring up the local environment

```shell
<DOCKER_COMPOSE> up
```

If you plan on developing the application you should also enable development mode.

```shell
<DOCKER_COMPOSE> run front-composer composer development-enable
<DOCKER_COMPOSE> exec viewer-app rm -f /tmp/config-cache.php
<DOCKER_COMPOSE> exec actor-app rm -f /tmp/config-cache.php

<DOCKER_COMPOSE> run api-composer composer development-enable
<DOCKER_COMPOSE> exec api-app rm -f /tmp/config-cache.php
```

The Viewer service will be available via http://localhost:9001/home

The Actor service will be available via http://localhost:9002/home

The API service will be available via http://localhost:9003

### Tests

To run the unit tests (the command for viewer-app and actor-app will run exactly the same suite of unit tests in the front service)

```shell
<DOCKER_COMPOSE> run viewer-app /app/vendor/bin/phpunit
<DOCKER_COMPOSE> run actor-app /app/vendor/bin/phpunit
<DOCKER_COMPOSE> run api-app /app/vendor/bin/phpunit
```

### Functional (Behat) test

To run the Behat smoke tests

```shell
<DOCKER_COMPOSE> \
  -f docker-compose.testing.yml run smoke-tests composer behat
```

### Updating composer dependencies

Composer install is run when the app container is built, and on a standard `docker-compose up`.

It can also be run independently with:

```shell
<DOCKER_COMPOSE> run api-composer

<DOCKER_COMPOSE> run front-composer
```

New packages can be added with:

```shell
<DOCKER_COMPOSE> run api-composer composer require author/package

<DOCKER_COMPOSE> run front-composer composer require author/package
```

Packages can be removed with:

```shell
<DOCKER_COMPOSE> run api-composer composer remove author/package

<DOCKER_COMPOSE> run front-composer composer remove author/package
```
