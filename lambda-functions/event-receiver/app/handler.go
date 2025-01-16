package main

import (
    "context"
    "encoding/json"
    "fmt"

    "github.com/aws/aws-lambda-go/events"
)

type SQSEventHandler interface {
    Handle(ctx context.Context, event events.SQSEvent) error
}

type sqsEventHandler struct {
    factory Factory
    logger Logger
}

func NewSQSEventHandler(factory Factory, logger Logger) SQSEventHandler {
    return &sqsEventHandler{
        factory: factory,
        logger: logger,
    }
}

func (h *sqsEventHandler) Handle(ctx context.Context, event events.SQSEvent) error {
    for _, record := range event.Records {
        h.logger.Info("Received SQS event")

        var payload map[string]interface{}
        err := json.Unmarshal([]byte(record.Body), &payload)
    		if err != nil {
    		    h.logger.Error("Failed to unmarshal event detail", err)
    		    return fmt.Errorf("failed to unmarshal event: %w", err)
    		}
            h.logger.Info("Processed SQS Event' event", "payload", payload)
    	}
    return nil

}