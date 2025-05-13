FROM golang:1.24.3-alpine@sha256:ef18ee7117463ac1055f5a370ed18b8750f01589f13ea0b48642f5792b234044 as build-env

WORKDIR /app

COPY . .

RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -a -installsuffix cgo -o proxy main.go

ENTRYPOINT [ "./entrypoint.sh" ]

CMD [ "./proxy" ]

EXPOSE 5000
