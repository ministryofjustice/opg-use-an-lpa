package main

import (
    "context"
    "encoding/json"
    "fmt"

    "github.com/aws/aws-lambda-go/events"
)

type CloudWatchHandler interfact {
    Handle(ctx context.Context, event events.CloudWatchHandler) error
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

func (h *cloudWatchHandler) Handle(ctx context.Context, event events.CloudWatchHandler) error {
    h.logger.Info("Received CloudWatch event", "source", event.Source)

    switch event.Source {
    case "opg.poas.makeregister":
        h.logger.Info("Handling 'makeregister' event", "detailType", event.DetailType)

    default:
        eventData, _ := json.Marshal(event)
        h.logger.Warn("Unhandled event source", fmt.Errorf("unknown source: %s", event.Source), "event", string(eventData))
        return fmt.Errorf("unknown event source: %s", event.Source)
    }

    return nil
}