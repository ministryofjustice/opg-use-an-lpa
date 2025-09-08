FROM node:22.19.0-alpine3.22@sha256:d2166de198f26e17e5a442f537754dd616ab069c47cc57b889310a717e0abbf9 AS asset-env

WORKDIR /app

COPY web/assets web/assets
COPY build.js .
COPY package.json package-lock.json ./

RUN npm ci && npm run build

# Build Go app
FROM golang:1.25.0-alpine@sha256:f18a072054848d87a8077455f0ac8a25886f2397f88bfdd222d6fafbb5bba440 AS build-env

RUN apk --no-cache add \
  build-base=0.5-r3 \
  ca-certificates=20250619-r0 \
  tzdata=2025b-r0 \
  && rm -rf /var/cache/apk/* \
  && adduser -D appuser

WORKDIR /app

COPY go.mod go.sum ./

RUN go mod download

COPY . .

RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -a -installsuffix cgo -o admin cmd/admin/main.go

# Bundle all
FROM scratch AS production

WORKDIR /go/bin

COPY --from=build-env /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/
COPY --from=build-env /etc/passwd /etc/passwd
COPY --from=build-env /app/admin admin
COPY --from=build-env /app/web/templates web/templates
COPY --from=asset-env /app/web/static web/static

USER appuser

ENTRYPOINT ["./admin"]
