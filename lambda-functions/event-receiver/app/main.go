package main

import (
	"context"

	"github.com/aws/aws-lambda-go/events"
	"github.com/aws/aws-lambda-go/lambda"
)

func main() {
	ctx := context.Background()

	logger := NewLogger()

	var err error

	cfg, err = config.LoadDefaultConfig(ctx)
	if err != nil {
		logger.ErrorContext(ctx, "failed to load default config", slog.Any("err", err))
		return
	}

    appConfig := LoadConfig()

    appFactory := NewFactory(cfg, logger, appConfig)

    handler := NewCloudWatchHandler(appFactory, logger)

    lambda.Start(handler)
}
