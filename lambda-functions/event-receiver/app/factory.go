package main

import (
	"log/slog"
	"net/http"
	"time"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
)

type Factory struct {
	logger       *slog.Logger
	now          func() time.Time
	cfg          aws.Config
	dynamoClient *dynamodb.Client
	appPublicURL string
	httpClient   *http.Client
}

func (f *Factory) DynamoClient() *dynamodb.Client {
	return f.dynamoClient
}

func (f *Factory) Logger() *slog.Logger {
	return f.logger
}
