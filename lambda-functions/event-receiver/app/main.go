package main

import (
	"context"
	"encoding/json"
	"errors"
	"log/slog"
	"net/http"
	"os"
	"time"

	"github.com/aws/aws-lambda-go/events"
	"github.com/aws/aws-lambda-go/lambda"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/ministryofjustice/opg-go-common/telemetry"
	"github.com/ministryofjustice/opg-use-an-lpa/lambda-functions/event-receiver/internal/dynamo"
)

var (
	tablePrefix    = os.Getenv("ENVIRONMENT")
	dynamoEndpoint = os.Getenv("AWS_ENDPOINT_DYNAMODB")
	actorMapTable  = "UserLpaActorMap"
	actorUserTable = "ActorUsers"
	sqsMsgId       = ""

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
	OneByIdentity(ctx context.Context, uid string, v any) error
	Put(ctx context.Context, tableName string, item map[string]types.AttributeValue) error
	ExistsLpaIDAndUserID(ctx context.Context, lpaUID string, userID string) (bool, error)
	PutUser(ctx context.Context, id, identity string) error
}

type Handler interface {
	EventHandler(context.Context, Factory, *events.CloudWatchEvent) error
}

func handler(ctx context.Context, factory Factory, event *events.SQSEvent) (map[string]any, error) {
	result := map[string]any{}
	batchItemFailures := []map[string]any{}
	var err error

	for _, record := range event.Records {
		sqsMsgId = record.MessageId
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
	cfg, err = config.LoadDefaultConfig(ctx,
		config.WithHTTPClient(http.DefaultClient),
		config.WithRegion("eu-west-1"),
	)

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

	dynamoClient, err := dynamo.NewClient(cfg, dynamoEndpoint, tablePrefix)
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
		httpClient:   httpClient,
	}

	lambda.Start(func(ctx context.Context, event events.SQSEvent) (map[string]any, error) {
		return handler(ctx, factory, &event)
	})
}
