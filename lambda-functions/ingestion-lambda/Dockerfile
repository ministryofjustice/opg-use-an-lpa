FROM golang:1.23.1-alpine AS base

WORKDIR /app

COPY go.mod go.sum ./

RUN go mod download

COPY app/ ./

RUN CGO_ENABLED=0 GOOS=linux GOARCH=arm64 go build -o /go/bin/ingestion ./app/ingestion

FROM scratch AS production

COPY --from=base /go/bin/ingestion ingestion

ENTRYPOINT ["./ingestion"]
