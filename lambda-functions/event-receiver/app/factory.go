package main

import (
	"log/slog"
	"net/http"
	"time"

	"github.com/aws/aws-sdk-go-v2/aws"
)

type DefaultFactory struct {
	logger       *slog.Logger
	now          func() time.Time
	uuidString   func() string
	cfg          aws.Config
	dynamoClient DynamodbClient
	appPublicURL string
	httpClient   *http.Client
}

func (f *DefaultFactory) Now() func() time.Time {
	return f.now
}

func (f *DefaultFactory) DynamoClient() DynamodbClient {
	return f.dynamoClient
}

func (f *DefaultFactory) UuidString() func() string {
	return f.uuidString
}

func (f *DefaultFactory) Logger() *slog.Logger {
	return f.logger
}
