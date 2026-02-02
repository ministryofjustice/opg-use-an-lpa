FROM node:24.11.0-alpine3.22@sha256:f36fed0b2129a8492535e2853c64fbdbd2d29dc1219ee3217023ca48aebd3787 AS asset-env

WORKDIR /app

COPY web/assets web/assets
COPY build.js .
COPY package.json package-lock.json ./

RUN npm ci --ignore-scripts && npm run build

# Build Go app
FROM golang:1.25.6-alpine3.23@sha256:98e6cffc31ccc44c7c15d83df1d69891efee8115a5bb7ede2bf30a38af3e3c92 AS build-env

RUN adduser -D appuser

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
