FROM golang:1.23.4-alpine AS base

WORKDIR /app

COPY go.mod go.sum ./

RUN go mod download

COPY app/ ./
COPY internal/ ./internal

RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -o /go/bin/main ./

FROM scratch AS production

WORKDIR /var/task

COPY --from=base /go/bin/main ./main

ENTRYPOINT ["./main"]
