FROM golang:1.25.5-alpine@sha256:26111811bc967321e7b6f852e914d14bede324cd1accb7f81811929a6a57fea9 AS build

WORKDIR /app

RUN apk add --no-cache zip=3.0-r13

COPY lambda-functions/event-receiver/go.mod lambda-functions/event-receiver/go.sum ./

RUN go mod download

COPY lambda-functions/event-receiver/app/ ./
COPY lambda-functions/event-receiver/internal/ ./internal

RUN GOOS=linux GOARCH=arm64 CGO_ENABLED=0 go build -tags lambda.norpc -o bootstrap . \
  && chmod 755 bootstrap \
  && zip event-receiver.zip bootstrap

FROM localstack/localstack:4.12.0@sha256:0df3a97da57de03a588c05d9b8f390f15c7033fc7c4512f94619d344bc3cd317 AS localstack

COPY --from=build /app/event-receiver.zip /event-receiver.zip

COPY ./localstack/localstack-init.sh /etc/localstack/init/ready.d/localstack-init.sh

RUN chmod 544 /etc/localstack/init/ready.d/localstack-init.sh
