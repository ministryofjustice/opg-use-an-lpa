# Service Admin

The Service Admin Portal is an internal tool for investigating queries, searching users' accounts and activation keys, and for internal reporting.

There are two components to run locally:

- Admin service
- JWT Proxy

## Usage

To run the service-admin service, you can use:

### VS Code

You should be able to use the "Launch" configuration to run the main app.
Run the proxy using the instructions in [the README](./proxy/README.md).

### Goland

Run both the "JWT Proxy" and the "Admin Service" inside your IDE

### Manual

Install `go`.

```shell
go mod download # Install dependencies
go run cmd/admin/main.go
```

The following environment variables will need to be set:

| Environment variable | Default |
|---|---------|
| `AWS_DYNAMODB_ENDPOINT` | `http://localhost:8000` |
| `ADMIN_JWT_SIGNING_KEY_URL` | `http://localhost:5000` |
| `AWS_ACCESS_KEY_ID` | - |
| `AWS_SECRET_ACCESS_KEY` | - |

Run the proxy using the instructions in [the README](./proxy/README.md).

You will be able to access the admin service at http://localhost:5000