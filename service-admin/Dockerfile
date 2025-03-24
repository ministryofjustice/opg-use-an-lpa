FROM node:22-alpine as asset-env

WORKDIR /app

COPY web/assets web/assets
COPY build.js .
COPY package.json package-lock.json ./

RUN npm ci && npm run build

# Build Go app
FROM golang:1.23.7-alpine as build-env

RUN apk --no-cache add \
  build-base=0.5-r3 \
  ca-certificates=20241121-r1 \
  tzdata=2025b-r0 \
  && rm -rf /var/cache/apk/* && update-ca-certificates && \
  adduser -D appuser

WORKDIR /app

COPY go.mod go.sum ./

RUN go mod download

COPY . .

RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -a -installsuffix cgo -o admin cmd/admin/main.go

# Bundle all
FROM scratch as production

WORKDIR /go/bin

COPY --from=build-env /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/
COPY --from=build-env /etc/passwd /etc/passwd
COPY --from=build-env /app/admin admin
COPY --from=build-env /app/web/templates web/templates
COPY --from=asset-env /app/web/static web/static

USER appuser

ENTRYPOINT ["./admin"]
