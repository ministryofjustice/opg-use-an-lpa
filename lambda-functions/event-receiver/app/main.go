package main

import (
	"context"
	"encoding/json"
	"errors"
	"github.com/aws/aws-lambda-go/events"
	"github.com/ministryofjustice/opg-use-an-lpa/internal/dynamo"
	"log/slog"
	"net/http"
	"os"
	"time"

	"github.com/aws/aws-lambda-go/lambda"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/ministryofjustice/opg-go-common/telemetry"
)

var (
	appPublicURL   = os.Getenv("APP_PUBLIC_URL")
	awsBaseURL     = os.Getenv("AWS_BASE_URL")
	actorMapTable  = "UserLpaActorMap"
	actorUserTable = "ActorUsers"

	cfg        aws.Config
	httpClient *http.Client
	logger     *slog.Logger
)

type Factory interface {
	Now() func() time.Time
	DynamoClient() DynamodbClient
	UuidString() func() string
}

type DynamodbClient interface {
	OneByUID(ctx context.Context, uid string, v any) error
	Put(ctx context.Context, tableName string, v any) error
	ExistsLpaIDAndUserID(ctx context.Context, lpaId string, userId string) (bool, error)
}

type Handler interface {
	EventHandler(context.Context, Factory, *events.CloudWatchEvent) error
}

func handler(ctx context.Context, factory Factory, event *events.SQSEvent) (map[string]any, error) {
	result := map[string]any{}
	batchItemFailures := []map[string]any{}
	var err error

	for _, record := range event.Records {
		if err = handleCloudWatchEvent(ctx, factory, record.Body); err != nil {
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

func handleCloudWatchEvent(ctx context.Context, factory Factory, body string) error {
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

		if err := eventHandler.EventHandler(ctx, factory, &cloudWatchEvent); err != nil {
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

		return errors.New("Unhandled event type: " + cloudWatchEvent.DetailType)
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

	dynamoClient, err := dynamo.NewClient(cfg)
	if err != nil {
		logger.ErrorContext(
			ctx,
			"Failed to create dynamodb client: "+err.Error(),
			slog.Group("location",
				slog.String("file", "main.go"),
			),
		)

		return
	}

	factory := &DefaultFactory{
		logger:       logger,
		cfg:          cfg,
		dynamoClient: dynamoClient,
		appPublicURL: appPublicURL,
		httpClient:   httpClient,
	}

	lambda.Start(func(ctx context.Context, event events.SQSEvent) (map[string]any, error) {
		return handler(ctx, factory, &event)
	})
}
