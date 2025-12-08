FROM golang:1.25.5-alpine@sha256:26111811bc967321e7b6f852e914d14bede324cd1accb7f81811929a6a57fea9 AS build-env

WORKDIR /app

COPY . .

RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -a -installsuffix cgo -o proxy main.go

ENTRYPOINT [ "./entrypoint.sh" ]

CMD [ "./proxy" ]

EXPOSE 5000
