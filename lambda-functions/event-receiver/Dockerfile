FROM golang:1.24.3-alpine@sha256:b4f875e650466fa0fe62c6fd3f02517a392123eea85f1d7e69d85f780e4db1c1 AS base

ENV CGO_ENABLED=0 GOOS=linux GOARCH=amd64

WORKDIR /app

COPY go.mod go.sum ./
RUN go mod download

COPY app/ ./
COPY internal/ ./internal

RUN go build -tags lambda.norpc -ldflags="-s -w" -o /go/bin/main ./

FROM gcr.io/distroless/static:nonroot@sha256:627d6c5a23ad24e6bdff827f16c7b60e0289029b0c79e9f7ccd54ae3279fb45f AS production

WORKDIR /var/task

COPY --from=base /go/bin/main ./main

ENTRYPOINT ["./main"]
