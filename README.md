# opg-use-my-lpa

OPG Use My LPA: Managed by opg-org-infra &amp; Terraform

![path to live status](https://github.com/ministryofjustice/opg-use-an-lpa/actions/workflows/path-to-live.yml/badge.svg)

[![codecov coverage status](https://codecov.io/gh/ministryofjustice/opg-use-an-lpa/branch/main/graph/badge.svg)](https://codecov.io/gh/ministryofjustice/opg-use-an-lpa)

[![repo standards badge](https://img.shields.io/badge/dynamic/json?color=blue&style=for-the-badge&logo=github&label=MoJ%20Compliant&query=%24.result&url=https%3A%2F%2Foperations-engineering-reports.cloud-platform.service.justice.gov.uk%2Fapi%2Fv1%2Fcompliant_public_repositories%2Fopg-use-an-lpa)](https://operations-engineering-reports.cloud-platform.service.justice.gov.uk/public-github-repositories.html#opg-use-an-lpa 'Link to report')

## Setup

Clone the following repository into the same base directory:

- [https://github.com/ministryofjustice/opg-use-an-lpa](https://github.com/ministryofjustice/opg-use-an-lpa)

Additionally, work related to Use is done in:
- [https://github.com/ministryofjustice/opg-data-lpa](https://github.com/ministryofjustice/opg-data-lpa)
- [https://github.com/ministryofjustice/opg-data-lpa-codes](https://github.com/ministryofjustice/opg-data-lpa-codes)
- [https://github.com/ministryofjustice/opg-opg-data-lpa-instructions-preferences](https://github.com/ministryofjustice/opg-data-lpa-instructions-preferences)


All commands assume a working directory of `opg-use-my-lpa`.

### Makefile

A Makefile is maintained that aliases the most useful docker-compose commands.

To build the service and it's dependencies

```shell
make build_all
```

To start the service and its dependencies

```shell
make up_all # start ual and all dependencies then run seeding of local data
```

To stop the service and its dependencies (ordering is important so that the networks are removed last)

```shell
make down_all # bring down everything including the lpa endpoint
```

There are other make file targets for common operations such as

`logs` to follow docker-compose logs for the service
`seed` to rerun seeding scripts to put or reset fixture data
`destroy` to stop the service and remove all images

Build images with no cache option.
Run this regularly to keep base docker images up to date,
as these include potential security fixes.

```shell
make rebuild [container name]
```

***Note:*** this can take several minutes to run.

To bring up the local environment

```shell
make up_all
```

If you plan on developing the application i:e in most cases, you should also enable development mode.

```shell
make development_mode
```

The Viewer service will be available via [http://localhost:9001/home](http://localhost:9001/home)

The Actor service will be available via [http://localhost:9002/home](http://localhost:9002/home)

The API service will be available via [http://localhost:9003](http://localhost:9003)

### Tests

To run all the unit tests (the command for viewer-app and actor-app will run exactly the same suite of unit tests in the front service)

```shell
make unit_test_all

# or, seperately
make unit_test_viewer_app
make unit_test_actor_app
make unit_test_javascript
```

### Functional (Behat) test

To run the Behat smoke tests

```shell
make smoke_tests
```

There other some other utility scripts in the composer.json
for example

```shell
cd service-api/app
composer run psalm

# to pass arguments to a composer script start with a double hyphen "--".
composer run int-test -- --verbose
```

### Updating composer dependencies

Composer install is run when the app container is built, and on a standard `docker-compose up`.

It can also be run independently with:

```shell
# Arbitrary commands such as "require author/package" or "remove author/package"
make run_api_composer -- [composer command]
make run_front_composer -- [composer command]

# explicit install or update
make run_api_composer_install
make run_api_composer_update

make run_front_composer_install
make run_front_composer_update
```

## Troubleshooting

There are occasions when your local dev environment doesn't quite act as it should. *Feel free to add further troubleshooting steps here.*

Here are some common problems we've come across:

### I cannot login with the seeded user

Its possible seeding of Use an LPA was not successful.
make sure all docker compose services are running and have settled first, then try again.
run the following command:

```shell
make seed
```

then try again

### I cannot add LPA's locally, which are in the seeded data set

Ensure that the api-gateway container is running

```shell
docker ps | grep opg-use-an-lpa-codes-gateway
```

If this is not running, you should re-run

```shell
make up_all
```

if that doesn't work try running
```shell
make update_mock
```
