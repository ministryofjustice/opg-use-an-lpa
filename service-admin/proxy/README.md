JWT signing development proxy
======

In order to use the same JWT authentication mechanism as provided by the AWS LoadBalancer
it is necessary to proxy requests to the admin service, ensuring that an appropriately
signed JWT token is attached to proxied requests so that the authentication middleware allows
your requests through.

## Usage

Files are supplied so that it's possible to build a functioning docker image that provides
the proxying service.

```shellscript
docker build -t admin-proxy:dev .
docker run --rm -e PROXY_HOST=http://localhost:9005 -p 5000:5000 admin-proxy:dev
```

Alternatively it is much easier to run it locally. The service runs on port 5000 by
default but that can be changed using configuration.

```shellscript
# generate key pair (only needs to be run the first time)
openssl ecparam -name prime256v1 -genkey -noout -out key.pem
openssl ec -in key.pem -pubout > pub-key.pem

go run main.go
```

## Configuration

You can configure the proxy in a number of ways but the defaults should realistically be correct
for your system.

| Flag | Environment Variable | Default | Description |
| --   | --                   | --      | --          |
| -port | PROXY_PORT | 5000 | The port to run the proxy on |
| -host | PROXY_HOST | http://127.0.0.1:9005 | The url of the application to proxy |
| -privkey | PROXY_PRIVATE_KEY | key.pem | The path to an ECDSA private key file |
| -pubkey | PROXY_PUBLIC_KEY | pub-key.pem | The path to the corresponding ECDSA public key file |
| -name | PROXY_CLAIM_NAME | Use Test User | A name to attach to the JWT claims |
| -email | PROXY_CLAIM_EMAIL | opg-use-an-lpa+test-user@digital.justice.gov.uk | An email address to attach to the JWT claims |
