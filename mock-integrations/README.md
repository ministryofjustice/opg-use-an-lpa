# Gateway mock

Mock servers which together mimic the data-lpa API.

Runs using [Prism](https://stoplight.io/open-source/prism), delivering the API specified in the latest version of the
[OPG Data LPA openapi doc](https://github.com/ministryofjustice/opg-data-lpa/blob/main/lambda_functions/v1/openapi/lpa-openapi.yml).

A Prism instance (data-lpa) and an nginx instance (api-gateway) are spun up as part of the docker-compose service to provide lightweight testing of the Use a Lasting Power of Attorney service without a full Sirius stack.

In `nginx.conf`, the IDs of LPAs are mapped to named examples added in `mock-openapi-examples.yaml`.
The example name is then used in the gateway nginx proxy to add a `Prefer: example=<name>` header to requests, which are forwarded to Prism. This `Prefer` header [instructs Prism to a return a particular Swagger example as a response](https://github.com/stoplightio/prism/blob/master/docs/guides/01-mocking.md#Response-Generation), allowing us to predictably return the correct LPA for a given request.

Any changes that are required to the opg-data-lpa openapi spec must be done in [opg-data-lpa](https://github.com/ministryofjustice/opg-data-lpa).

## Prerequisites

You will need yq and wget to work with this mock!

```shell
brew install yq wget
```

## Using the mock

To start the mock alone, run

```shell
make up_mock
```

Or bring up the whole service

```shell
make up_all
```

Once started, when there are updates to the openapi spec for opg-data-lpa, or the examples, run an update

```shell
make update_mock
```

This will generate a new mock-openapi.yaml file and restart the mock docker compose services.

## Viewing the examples

It's possible to invoke the mock endpoints using curl or similar as follows:

```shell
# Valid LPA Uid
curl -i -H "Authorization: sigv4 x" -k http://localhost:7010/use-an-lpa/lpas/700000000047

# LPA not found
curl -i -H "Authorization: sigv4 x" -k http://localhost:7010/use-an-lpa/lpas/700000000000
```

The URLs above cause Prism to return examples with specific statuses, as shown.
Any other LPA ID (the last part of the path) will return a "Received" status.

## Working on the examples

Yaml examples can be generated from the data-lpa api using a script in ./mock-integrations/generate_examples

Add LPAs from the Sirius Integration environment to list.txt (each Uid as a new line), then run the script.

```shell
cd ./mock-integrations/generate_examples

aws-vaul exec identity -- ./make_yaml.sh
```

Review the output in ./mock-integrations/generate_examples/mock-openapi-examples.yaml and if happy copy to ./mock-integrations/mock-openapi-examples.yaml, replacing the old file.
