package main

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"log/slog"
	"net/http"
	"os"
	"time"

	"github.com/aws/aws-lambda-go/events"
	"github.com/aws/aws-lambda-go/lambda"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/config"
)

var (
	cfg        aws.Config
	httpClient *http.Client
	logger     *slog.Logger
)

type factory interface {
}

type Handler interface {
	Handle(context.Context, *events.SQSEvent) error
}

type Event struct {
	SQSEvent *events.SQSEvent
}

func (e *Event) UnmarshalJSON(data []byte) error {
	var sqs events.SQSEvent
	if err := json.Unmarshal(data, &sqs); err == nil && len(sqs.Records) > 0 && sqs.Records[0].MessageId != "" {
		e.SQSEvent = &sqs
		return nil
	}

	return errors.New("unknown event type")
}

func handler(ctx context.Context, event Event) (map[string]any, error) {
	result := map[string]any{}

	if event.SQSEvent != nil {
		batchItemFailures := []map[string]any{}
		for _, record := range event.SQSEvent.Records {
			var sqsEvent *events.SQSEvent
			if err := json.Unmarshal([]byte(record.Body), &sqsEvent); err != nil {
				logger.ErrorContext(ctx, "could not unmarshal event", slog.String("messageID", record.MessageId), slog.Any("err", err))
				batchItemFailures = append(batchItemFailures, map[string]any{"itemIdentifier": record.MessageId})
				continue
			}

			if err := handleSQSEvent(ctx, sqsEvent); err != nil {
				logger.ErrorContext(ctx, "error processing event", slog.String("messageID", record.MessageId), slog.Any("err", err))
				batchItemFailures = append(batchItemFailures, map[string]any{"itemIdentifier": record.MessageId})
				continue
			}
		}

		result["batchItemFailures"] = batchItemFailures
		return result, nil
	}

	return result, nil
}

func handleSQSEvent(ctx context.Context, sqsEvent *events.SQSEvent) error {
	handler := &makeregisterEventHandler{}

	for _, record := range sqsEvent.Records {
		logger.InfoContext(ctx, "handling message", slog.String("MessageId", record.MessageId))

		if err := handler.Handle(ctx, &record); err != nil {
			return fmt.Errorf("%s: %w", record.MessageId, err)
		}

		logger.InfoContext(ctx, "successfully handled message", slog.String("MessageId", record.MessageId))
		return nil
	}

	return nil
}

func main() {
	ctx := context.Background()

	httpClient = &http.Client{Timeout: 30 * time.Second}

	logger = slog.New(slog.
		NewJSONHandler(os.Stdout, &slog.HandlerOptions{
			ReplaceAttr: func(_ []string, a slog.Attr) slog.Attr {
				switch a.Value.Kind() {
				case slog.KindAny:
					switch v := a.Value.Any().(type) {
					case *http.Request:
						return slog.Group(a.Key,
							slog.String("method", v.Method),
							slog.String("uri", v.URL.String()))
					}
				}

				return a
			},
		}))

	var err error
	cfg, err = config.LoadDefaultConfig(ctx)
	if err != nil {
		logger.ErrorContext(ctx, "failed to load default config", slog.Any("err", err))
		return
	}

	lambda.Start(handler)

}
