package main

import (
	"context"
	"encoding/json"
	"fmt"
	"github.com/aws/aws-lambda-go/events"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"log/slog"

	"github.com/google/uuid"
)

type MakeRegisterEventHandler struct{}

type Actor struct {
	ActorUID  string `json:"actorUid"`
	SubjectID string `json:"subjectId"`
}

type lpaAccessGranted struct {
	UID     string  `json:"uid"`
	LpaType string  `json:"lpaType"`
	Actors  []Actor `json:"actors"`
}

func (h *MakeRegisterEventHandler) EventHandler(ctx context.Context, factory Factory, record *events.CloudWatchEvent) error {

	var data lpaAccessGranted

	if err := json.Unmarshal(record.Detail, &data); err != nil {
		errMsg := "failed to unmarshal CloudWatch detail :" + record.ID + " - Error: " + err.Error()

		logger.ErrorContext(
			ctx,
			errMsg,
			slog.Group("location",
				slog.String("file", "makeregister_event_handler.go"),
			),
		)
		return err
	}

	for _, actor := range data.Actors {
		if err := handleUsers(ctx, factory.DynamoClient(), actor); err != nil {
			logger.ErrorContext(
				ctx,
				err.Error(),
				slog.Group("location",
					slog.String("file", "makeregister_event_handler.go"),
				),
			)

			return err
		}
	}

	return nil
}

func handleUsers(ctx context.Context, dynamoClient DynamodbClient, actor Actor) error {
	var existingUser Actor

	err := dynamoClient.OneByUID(ctx, actor.SubjectID, &existingUser)

	if err != nil {
		return fmt.Errorf("Failed to find existing user %s: %w", actor.ActorUID, err)
	}

	newUser := map[string]types.AttributeValue{
		"Id":       &types.AttributeValueMemberS{Value: uuid.New().String()},
		"Identity": &types.AttributeValueMemberS{Value: actor.SubjectID},
	}

	err = dynamoClient.Put(ctx, newUser)
	if err != nil {
		return fmt.Errorf("Failed to put user %s: %w", actor.ActorUID, err)
	}

	return nil
}
