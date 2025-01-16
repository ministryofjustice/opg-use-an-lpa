package main

import (
	"context"

	"github.com/aws/aws-lambda-go/lambda"
    "github.com/aws/aws-sdk-go-v2/config"
    "github.com/aws/aws-sdk-go-v2/aws"
)

var (
	cfg        aws.Config
)

func main() {
	ctx := context.Background()

	logger := NewLogger()

	var err error

	cfg, err = config.LoadDefaultConfig(ctx)
	if err != nil {
		logger.Error("failed to load default config", err)
		return
	}

    appFactory := NewFactory(cfg, logger)

    handler := NewSQSEventHandler(appFactory, logger)

    lambda.Start(handler)
}
