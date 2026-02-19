FROM golang:1.25.7-alpine3.23@sha256:f6751d823c26342f9506c03797d2527668d095b0a15f1862cddb4d927a7a4ced AS build

WORKDIR /app

RUN apk add --no-cache zip=3.0-r13

COPY lambda-functions/event-receiver/go.mod lambda-functions/event-receiver/go.sum ./

RUN go mod download

COPY lambda-functions/event-receiver/app/ ./
COPY lambda-functions/event-receiver/internal/ ./internal

RUN GOOS=linux GOARCH=arm64 CGO_ENABLED=0 go build -tags lambda.norpc -o bootstrap . \
  && chmod 755 bootstrap \
  && zip event-receiver.zip bootstrap

FROM localstack/localstack:4.1.0@sha256:cd576d5d92a8506702a920e7fbc97bf3c8452143f2487ae421ad16e37d2d3fab AS localstack

COPY --from=build /app/event-receiver.zip /event-receiver.zip

COPY ./localstack/localstack-init.sh /etc/localstack/init/ready.d/localstack-init.sh

RUN chmod 544 /etc/localstack/init/ready.d/localstack-init.sh
