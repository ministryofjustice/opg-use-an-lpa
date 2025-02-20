FROM golang:1.23.5-alpine AS build

WORKDIR /app

RUN apk add --no-cache zip=3.0-r13 

COPY lambda-functions/event-receiver/go.mod lambda-functions/event-receiver/go.sum ./

RUN go mod download

COPY lambda-functions/event-receiver/app/ ./
COPY lambda-functions/event-receiver/internal/ ./internal

RUN GOOS=linux GOARCH=arm64 CGO_ENABLED=0 go build -tags lambda.norpc -o bootstrap . \
  && chmod 755 bootstrap \
  && zip event-receiver.zip bootstrap

FROM localstack/localstack:4.1.0 AS localstack

COPY --from=build /app/event-receiver.zip /event-receiver.zip

COPY ./localstack/localstack-init.sh /etc/localstack/init/ready.d/localstack-init.sh

RUN chmod 544 /etc/localstack/init/ready.d/localstack-init.sh
