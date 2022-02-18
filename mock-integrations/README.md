# Gateway mock

Mock servers which together mimic the data-lpa gateway API.

Runs using [Prism](https://stoplight.io/open-source/prism), delivering
the API specified in the latest version of the
[Sirius Swagger doc](https://github.com/ministryofjustice/opg-sirius-api-gateway/blob/master/docs/swagger.v1.yaml).

A Prism instance (mocksirius) and an nginx instance (gateway) are spun up as
part of the docker-compose script in dev to enable lightweight testing of the
Make an LPA tool without a full Sirius stack.

This mock uses data which matches
[the data set up in the pre-prod Sirius instance](https://opgtransform.atlassian.net/wiki/spaces/LDS/pages/1289191456/Testing+Track+my+LPA+Status). In turn, the Sirius pre-prod data
corresponds with the seeding data used in Make (see `seeding` directory in
the root of this project).

Specifically, the IDs of X LPAs are mapped to examples added to the Sirius
Swagger YAML: see `swagger-examples.yaml` for the examples themselves. The
mapping goes from an LPA ID to an example name (see `nginx.conf`, which
contains the mapping). The example name is then used in the gateway nginx proxy
to add a `Prefer: example=<name>` header to requests, which are then forwarded
to the Prism proxy. This `Prefer` header
[instructs Prism to a return a particular Swagger example as a response](https://github.com/stoplightio/prism/blob/master/docs/guides/01-mocking.md#Response-Generation),
allowing us to predictably show a status for an LPA. Without this intermediate proxy,
Prism can only return a single baked response, or dynamic responses with
random data which varies for every request.

## Viewing the examples

It's possible to invoke the mock endpoints using curl or similar as follows:

```shell
# Valid LPA Uid
curl -i -H "Authorization: sigv4 x" -k http://localhost:7010/use-an-lpa/lpas/700000000047

# Valid Meris Id with 1 LPA
curl -i -H "Authorization: sigv4 x" -k http://localhost:7010/use-an-lpa/lpas/3000000

# Valid Meris Id with many LPAs
curl -i -H "Authorization: sigv4 x" -k http://localhost:7010/use-an-lpa/lpas/2000000

# Not found
curl -i -H "Authorization: sigv4 x" -k http://localhost:7010/use-an-lpa/lpas/2000001
```

The URLs above cause Prism to return examples with specific statuses, as shown.
Any other LPA ID (the last part of the path) will return a "Received" status.

Yaml examples can be generated from the data-lpa api using yq

```shell
aws-vaul exec identity -- python ./scripts/call-api-gateway/call_api_gateway.py 700000000047 | yq -P
```

## Working on the scripts

We use a script to add our own modifications to the Sirius Swagger file
(for now, at least). These require python3 plus some dependencies. If you're
working on these scripts (in the `scripts/` directory), follow these
instructions to set up your environment:

```shell
virtualenv -p python3 ~/venv/gatewaymock
source ~/venv/gatewaymock/bin/activate
pip install -r requirements.txt
```

You should now be able to work on the scripts in the `scripts/` directory.
