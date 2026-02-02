FROM golang:1.25.6-alpine3.23@sha256:98e6cffc31ccc44c7c15d83df1d69891efee8115a5bb7ede2bf30a38af3e3c92 AS base

ENV CGO_ENABLED=0 GOOS=linux GOARCH=amd64

WORKDIR /app

COPY go.mod go.sum ./
RUN go mod download

COPY app/ ./
COPY internal/ ./internal

RUN go build -tags lambda.norpc -ldflags="-s -w" -o /go/bin/main ./

FROM gcr.io/distroless/static:nonroot@sha256:e8a4044e0b4ae4257efa45fc026c0bc30ad320d43bd4c1a7d5271bd241e386d0 AS production

WORKDIR /var/task

COPY --from=base /go/bin/main ./main

ENTRYPOINT ["./main"]
