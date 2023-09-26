FROM golang:1.20 as build-env

WORKDIR /app

COPY . .

RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -a -installsuffix cgo -o onelogin main.go

CMD [ "./onelogin" ]

EXPOSE 8080
