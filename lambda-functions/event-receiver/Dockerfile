FROM golang:1.27.3-alpine3.23@sha256:754d5200fc3edae76d3b6598a9a3fac7e74981ee6a5413ab955025f185753ef2 AS base

ENV CGO_ENABLED=0 GOOS=linux GOARCH=amd64

WORKDIR /app

COPY go.mod go.sum ./
RUN go mod download

COPY app/ ./
COPY internal/ ./internal

RUN go build -tags lambda.norpc -ldflags="-s -w" -o /go/bin/main ./

FROM public.ecr.aws/lambda/provided:al2023@sha256:ca6c7b9e8d09041cf1d48d8caacdcad27215ca3266e4fde8ce7f5f2ccb5c2113 AS rie

FROM base AS local-build
ARG TARGETARCH
RUN GOARCH=${TARGETARCH} go build -tags lambda.norpc -ldflags="-s -w" -o /go/bin/local-main ./

FROM gcr.io/distroless/static:nonroot@sha256:18f4cc26c9ffa7f20a359298be7deadac5c607c8e03a02b8119f1a5fc2f82c99 AS local
WORKDIR /var/task
COPY --from=local-build /go/bin/local-main ./main
COPY --from=rie /usr/local/bin/aws-lambda-rie /aws-lambda-rie
ENTRYPOINT ["/aws-lambda-rie", "./main"]

FROM gcr.io/distroless/static:nonroot@sha256:18f4cc26c9ffa7f20a359298be7deadac5c607c8e03a02b8119f1a5fc2f82c99 AS production

WORKDIR /var/task

COPY --from=base /go/bin/main ./main

ENTRYPOINT ["./main"]
