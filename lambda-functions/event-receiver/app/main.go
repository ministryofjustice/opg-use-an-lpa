package main

import (
	"context"
	"encoding/json"
	"errors"
	"github.com/aws/aws-lambda-go/events"
	"log/slog"
	"net/http"
	"os"
	"time"

	"github.com/aws/aws-lambda-go/lambda"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/ministryofjustice/opg-go-common/telemetry"
	"github.com/ministryofjustice/opg-use-an-lpa/internal/dynamo"
	"github.com/ministryofjustice/opg-use-an-lpa/internal/random"
)

var (
	tableName    = os.Getenv("LPAS_TABLE")
	appPublicURL = os.Getenv("APP_PUBLIC_URL")
	awsBaseURL   = os.Getenv("AWS_BASE_URL")

	cfg        aws.Config
	httpClient *http.Client
	logger     *slog.Logger
)

type factory interface {
	Now() func() time.Time
	DynamoClient() DynamodbClient
	UuidString() func() string
}

type DynamodbClient interface {
	OneByUID(ctx context.Context, uid string, v any) error
	Put(ctx context.Context, v any) error
}

type Handler interface {
	EventHandler(context.Context, factory, *events.CloudWatchEvent, factory) error
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

func handler(ctx context.Context, factory *Factory, event Event) (map[string]any, error) {
	result := map[string]any{}
	batchItemFailures := []map[string]any{}

	dynamoClient, err := dynamo.NewClient(cfg, tableName)
	if err != nil {
		logger.ErrorContext(
			ctx,
			"Failed to create dynamodb client: "+err.Error(),
			slog.Group("location",
				slog.String("file", "main.go"),
			),
		)

		return result, err
	}

	factory = &Factory{
		logger:       logger,
		now:          time.Now,
		uuidString:   random.UuidString,
		cfg:          cfg,
		dynamoClient: dynamoClient,
		appPublicURL: appPublicURL,
		httpClient:   httpClient,
	}

	for _, record := range event.SQSEvent.Records {
		if err = handleCloudWatchEvent(ctx, record.Body, factory); err != nil {
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

func handleCloudWatchEvent(ctx context.Context, body string, factory *Factory) error {
	var cloudWatchEvent events.CloudWatchEvent

	err := json.Unmarshal([]byte(body), &cloudWatchEvent)
	if err != nil {
		logger.ErrorContext(
			ctx,
			"Failed to Unmarshal CloudWatch Event",
			slog.Group("location",
				slog.String("file", "main.go"),
			),
		)
		return err
	}

	if cloudWatchEvent.DetailType == "lpa-access-granted" {
		eventHandler := &MakeRegisterEventHandler{}

		if err := eventHandler.EventHandler(ctx, &cloudWatchEvent, factory); err != nil {
			logger.ErrorContext(
				ctx,
				"Failed to handle cloudwatch event: "+err.Error(),
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

		return errors.New("unhandled Event Type")
	}

	return nil
}

func main() {
	ctx := context.Background()

	httpClient = &http.Client{Timeout: 30 * time.Second}

	logger = telemetry.NewLogger("opg-use-an-lpa/event-receiver")

	var err error
	cfg, err = config.LoadDefaultConfig(ctx, config.WithHTTPClient(http.DefaultClient))
	if err != nil {
		logger.ErrorContext(
			ctx,
			"Failed to load default config",
			slog.Group("location",
				slog.String("file", "main.go"),
			),
		)
		return
	}

	if len(awsBaseURL) > 0 {
		cfg.BaseEndpoint = aws.String(awsBaseURL)
	}

	lambda.Start(handler)
}
