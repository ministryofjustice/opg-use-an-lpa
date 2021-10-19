# opg-use-my-lpa

OPG Use My LPA: Managed by opg-org-infra &amp; Terraform

[![CircleCI](https://circleci.com/gh/ministryofjustice/opg-use-an-lpa.svg?style=svg)](https://circleci.com/gh/ministryofjustice/opg-use-an-lpa)
[![Coverage Status](https://coveralls.io/repos/github/ministryofjustice/opg-use-an-lpa/badge.svg?branch=main)](https://coveralls.io/github/ministryofjustice/opg-use-an-lpa?branch=main)
[![codecov coverage status](https://codecov.io/gh/ministryofjustice/opg-use-an-lpa/branch/main/graph/badge.svg)](https://codecov.io/gh/ministryofjustice/opg-use-an-lpa)
## Setup

Clone the following two repositories into the same base directory:

- https://github.com/ministryofjustice/opg-use-an-lpa
- https://github.com/ministryofjustice/opg-data-lpa
- https://github.com/ministryofjustice/opg-data-lpa-codes

All commands assume a working directory of `opg-use-my-lpa`.

### Makefile

A Makefile is maintained that aliases the most useful docker-compose commands.

To build the service and it's dependencies

```shell
make build # build ual and lpa-codes
make build --directory=../opg-data-lpa/ # build lpas-collection endpoint

# or

make build_all
```

To start the service and its dependencies

```shell
make up # start ual and lpa-codes then run seeding
make up-bridge-ual create_secrets --directory=../opg-data-lpa/ # start lpas-collection endpoint

# alternatively

make up_all # start ual and all dependencies then run seeding of local data
```

To stop the service and its dependencies (ordering is important so that the networks are removed last)

```shell
make down-bridge-ual --directory=../opg-data-lpa/ # bring down the lpas-collection endpoint
make down # bring down ual and lpa-codes

# alternatively

make down_all # bring down everything including the lpa endpoint
```

There are other make file targets for common operations such as

`logs` to follow docker-compose logs for the service
`seed` to rerun seeding scripts to put or reset fixture data
`destroy` to stop the service and remove all images

### Docker-Compose

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

## Troubleshooting
There are occasions when your local dev environment doesn't quite act as it should.
_Feel free to add further troubleshooting steps here._

Here are some common problems we've come across:

### I cannot login with the seeded user.

Its possible seeding of Use an LPA was not successful.
make sure all docker compose services are running and have settled first, then try again.
run the following command:
```shell
<DOCKER_COMPOSE> run api-seeding
```
then try again

### I cannot add  LPA's locally, which are in the seeded data set.

This could be because the LPA Gateway (Sirius Gateway) has not been properly initialised.
make sure all docker compose services are running andhave settled first, then try again.
If still not working,run the following command:
```shell
<DOCKER_COMPOSE> run lpa-gateway-setup
```
if that doesn't work try running the api-seeding step, mentioned with the login failure error.
