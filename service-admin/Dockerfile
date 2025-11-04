FROM node:22.16.0-alpine3.22@sha256:41e4389f3d988d2ed55392df4db1420ad048ae53324a8e2b7c6d19508288107e AS asset-env

WORKDIR /app

COPY web/assets web/assets
COPY build.js .
COPY package.json package-lock.json ./

RUN npm ci --ignore-scripts && npm run build

# Build Go app
FROM golang:1.25.3-alpine@sha256:aee43c3ccbf24fdffb7295693b6e33b21e01baec1b2a55acc351fde345e9ec34 AS build-env

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
