package main

import (
	"context"
	"encoding/json"
	"errors"
	"github.com/aws/aws-lambda-go/events"
	"log/slog"
	"net/http"
	"time"

	"github.com/aws/aws-lambda-go/lambda"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/ministryofjustice/opg-go-common/telemetry"
)

var (
	cfg        aws.Config
	httpClient *http.Client
	logger     *slog.Logger
)

type factory interface {
}

type Handler interface {
	EventHandler(context.Context, *events.CloudWatchEvent) error
}

type Event struct {
	SQSEvent        *events.SQSEvent
	CloudWatchEvent *events.CloudWatchEvent
}

func (e *Event) UnmarshalJSON(data []byte) error {
	var sqs events.SQSEvent
	if err := json.Unmarshal(data, &sqs); err == nil && len(sqs.Records) > 0 && sqs.Records[0].MessageId != "" {
		e.SQSEvent = &sqs
		return nil
	}

	return errors.New("unknown event type")
}

func handler(ctx context.Context, event events.SQSEvent) (map[string]any, error) {

	result := map[string]any{}
	var err error
	batchItemFailures := []map[string]any{}
	for _, record := range event.Records {
		if err = handleCloudWatchEvent(ctx, record.Body); err != nil {
			logger.ErrorContext(
				ctx,
				err.Error(),
				slog.Group("location",
					slog.String("file", "main.go"),
				),
			)

			batchItemFailures = append(batchItemFailures, map[string]any{"itemIdentifier": record.MessageId})
			continue
		}
	}

	result["batchItemFailures"] = batchItemFailures
	return result, err
}

func handleCloudWatchEvent(ctx context.Context, body string) error {
	var cloudWatchEvent events.CloudWatchEvent

	err := json.Unmarshal([]byte(body), &cloudWatchEvent)
	if err != nil {
		logger.ErrorContext(
			ctx,
			"Failed to unmarshal CloudWatch Event",
			slog.Group("location",
				slog.String("file", "main.go"),
			),
		)
		return err
	}

	if cloudWatchEvent.DetailType == "lpa-access-granted" {
		var eventHandler Handler
		eventHandler = &MakeRegisterEventHandler{}

		if err := eventHandler.EventHandler(ctx, &cloudWatchEvent); err != nil {
			logger.ErrorContext(
				ctx,
				"Failed to handle cloudwatch event",
				slog.Group("location",
					slog.String("file", "main.go"),
				),
			)
			return err
		}
	} else {
		logger.ErrorContext(
			ctx,
			"Unhandled event type",
			slog.Group("location",
				slog.String("file", "main.go"),
			),
		)

		return errors.New("Unhandled event type")
	}
	return nil
}

func main() {
	ctx := context.Background()

	httpClient = &http.Client{Timeout: 30 * time.Second}

	logger = telemetry.NewLogger("opg-use-an-lpa/event-receiver")

	var err error
	cfg, err = config.LoadDefaultConfig(ctx)
	if err != nil {
		logger.InfoContext(
			ctx,
			"Failed to load default config",
			slog.Group("location",
				slog.String("file", "main.go"),
			),
		)
	}

	lambda.Start(handler)
}
