FROM node:22.23.4-bookworm-slim@sha256:de170b39439eb0e5a3444ad7d60b015614a381e10fecae2e30ec9e735122f38d AS node

FROM ghcr.io/astral-sh/uv:0.13.5@sha256:32a7bb39b496babbd997b0a39a4d66c126bdaf5661b302bf6f06abda8deca6ff AS uv

FROM golang:1.27.3-alpine3.23@sha256:754d5200fc3edae76d3b6598a9a3fac7e74981ee6a5413ab955025f185753ef2 AS build

WORKDIR /app

RUN apk add --no-cache zip=3.0-r13

COPY lambda-functions/event-receiver/go.mod lambda-functions/event-receiver/go.sum ./

RUN go mod download

COPY lambda-functions/event-receiver/app/ ./
COPY lambda-functions/event-receiver/internal/ ./internal

RUN GOOS=linux GOARCH=arm64 CGO_ENABLED=0 go build -tags lambda.norpc -o bootstrap . \
  && chmod 755 bootstrap \
  && zip event-receiver.zip bootstrap

FROM localstack/localstack:4.14.0@sha256:3ebc37595918b8accb852f8048fef2aff047d465167edd655528065b07bc364a AS localstack

# Keep the token-free release while patching its bundled dependencies.
COPY --from=node /usr/local/bin/node /usr/local/bin/node

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates=20250419+deb13u1 \
    && rm -rf /var/lib/apt/lists/*

RUN --mount=from=uv,source=/uv,target=/usr/local/bin/uv \
    uv pip install --python /usr/local/bin/python3 --no-deps certifi==2026.10.23 \
    && uv pip install --python /opt/code/localstack/.venv/bin/python --no-deps certifi==2026.10.23

COPY --from=build /app/event-receiver.zip /event-receiver.zip

COPY ./localstack/localstack-init.sh /etc/localstack/init/ready.d/localstack-init.sh

RUN chmod 544 /etc/localstack/init/ready.d/localstack-init.sh
