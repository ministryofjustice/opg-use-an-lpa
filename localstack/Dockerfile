FROM golang:1.23.5-alpine AS build

RUN apk add --no-cache zip=3.0-r13 #unzip=6.0-r15

WORKDIR /app

#RUN go mod download

COPY --link cmd/event-received ./cmd/event-received

RUN GOOS=linux GOARCH=arm64 go build -tags lambda.norpc -o bootstrap main.go \
  && chmod 755 event-receiver/bootstrap \
  && zip event-receiver.zip bootstrap

FROM localstack/localstack:4.1.0 AS localstack

COPY --from=build /app/event-received.zip /etc/event-receiver.zip

COPY ./localstack/localstack-init.sh /etc/localstack/init/ready.d/localstack-init.sh

RUN chmod 544 /etc/localstack/init/ready.d/localstack-init.sh
