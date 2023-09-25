FROM node:20-alpine as asset-env

WORKDIR /app

COPY web/assets web/assets
COPY build.js .
COPY package.json package-lock.json ./

RUN npm ci && npm run build

# Build Go app
FROM golang:1.21.0-alpine as build-env

RUN apk --update --no-cache add build-base

WORKDIR /app

COPY go.mod go.sum ./

RUN go mod download

COPY . .

RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -a -installsuffix cgo -o admin cmd/admin/main.go

# Bundle all
FROM alpine:3.18 as production

WORKDIR /go/bin

RUN apk --update --no-cache add \
  ca-certificates \
  tzdata \
  && rm -rf /var/cache/apk/*

RUN apk upgrade libcrypto3 libssl3

COPY --from=build-env /app/admin admin
COPY --from=build-env /app/web/templates web/templates
COPY --from=asset-env /app/web/static web/static
ENTRYPOINT ["./admin"]
