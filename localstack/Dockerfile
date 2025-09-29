FROM golang:1.25.1-alpine@sha256:b6ed3fd0452c0e9bcdef5597f29cc1418f61672e9d3a2f55bf02e7222c014abd AS build

WORKDIR /app

RUN apk add --no-cache zip=3.0-r13

COPY lambda-functions/event-receiver/go.mod lambda-functions/event-receiver/go.sum ./

RUN go mod download

COPY lambda-functions/event-receiver/app/ ./
COPY lambda-functions/event-receiver/internal/ ./internal

RUN GOOS=linux GOARCH=arm64 CGO_ENABLED=0 go build -tags lambda.norpc -o bootstrap . \
  && chmod 755 bootstrap \
  && zip event-receiver.zip bootstrap

FROM localstack/localstack:4.8.1@sha256:8c9756bddc44625cd6e890f5e4bcc29b045eebe108b268482b848f4f832ec153 AS localstack

COPY --from=build /app/event-receiver.zip /event-receiver.zip

COPY ./localstack/localstack-init.sh /etc/localstack/init/ready.d/localstack-init.sh

RUN chmod 544 /etc/localstack/init/ready.d/localstack-init.sh
