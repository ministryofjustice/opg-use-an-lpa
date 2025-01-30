package main

import (
	"log/slog"
	"net/http"
	"time"

	"github.com/aws/aws-sdk-go-v2/aws"
)

type Factory struct {
	logger       *slog.Logger
	now          func() time.Time
	uuidString   func() string
	cfg          aws.Config
	dynamoClient DynamodbClient
	appPublicURL string
	httpClient   *http.Client
}

func (f *Factory) Now() func() time.Time {
	return f.now
}

func (f *Factory) DynamoClient() DynamodbClient {
	return f.dynamoClient
}

func (f *Factory) UuidString() func() string {
	return f.uuidString
}

func (f *Factory) Logger() *slog.Logger {
	return f.logger
}
