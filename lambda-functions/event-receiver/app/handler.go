package main

import (
    "context"
    "encoding/json"
    "fmt"

    "github.com/aws/aws-lambda-go/events"
)

type CloudWatchHandler interface {
    Handle(ctx context.Context, event events.CloudWatchEvent) error
}

type cloudWatchHandler struct {
    factory Factory
    logger Logger
}

func NewCloudWatchHandler(factory Factory, logger Logger) CloudWatchHandler {
    return &cloudWatchHandler{
        factory: factory,
        logger: logger,
    }
}

func (h *cloudWatchHandler) Handle(ctx context.Context, event events.CloudWatchEvent) error {
    var parsedEvent events.CloudWatchEvent

    h.logger.Info("Received CloudWatch event", "source", event.Source)

    err := json.Unmarshal(event.Detail, &parsedEvent)
    if err != nil {
        h.logger.Error("Failed to unmarshal event detail", err)
        return fmt.Errorf("failed to unmarshal event: %w", err)
    }

    switch parsedEvent.Source {
    case "opg.poas.makeregister":
        h.logger.Info("Handling 'makeregister' event", "detailType", parsedEvent.DetailType)
    default:
        h.logger.Warn("Unhandled event source: " + parsedEvent.Source, err)
    }

    return nil
}