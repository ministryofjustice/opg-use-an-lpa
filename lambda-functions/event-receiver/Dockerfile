FROM golang:1.24.3-alpine@sha256:ef18ee7117463ac1055f5a370ed18b8750f01589f13ea0b48642f5792b234044 AS base

WORKDIR /app

COPY go.mod go.sum ./

RUN go mod download

COPY app/ ./
COPY internal/ ./internal

RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -o /go/bin/main ./

FROM public.ecr.aws/lambda/provided:al2023.2025.05.04.04@sha256:fed9eb1f995d9c1f714794e3c2223fd5a97990022eedbab6f6f0d711ba888ac6 AS production

# Switch DNF to the latest AL2023.7 release (2023.7.20250512) and update SQLite packages
RUN echo "2023.7.20250512" > /etc/dnf/vars/releasever && \
    dnf clean all && \
    dnf -y update sqlite-libs libxml2 && \
    dnf clean all

WORKDIR /var/task

COPY --from=base /go/bin/main ./main

ENTRYPOINT ["./main"]
