FROM golang:1.24.2-alpine AS base

WORKDIR /app

COPY go.mod go.sum ./

RUN go mod download

COPY app/ ./
COPY internal/ ./internal

RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -o /go/bin/main ./

FROM public.ecr.aws/lambda/provided:al2023.2025.05.04.04@sha256:fed9eb1f995d9c1f714794e3c2223fd5a97990022eedbab6f6f0d711ba888ac6 AS production

WORKDIR /var/task

COPY --from=base /go/bin/main ./main

ENTRYPOINT ["./main"]
