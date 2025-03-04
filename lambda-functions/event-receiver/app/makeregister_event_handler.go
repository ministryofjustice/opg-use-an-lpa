package main

import (
	"context"
	"encoding/json"
	"fmt"
	"github.com/aws/aws-lambda-go/events"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"log/slog"
	"time"

	"github.com/google/uuid"
)

type MakeRegisterEventHandler struct{}

type Actor struct {
	ActorUID  string `json:"actorUid"`
	SubjectID string `json:"subjectId"`
	userId    string
}

type lpaAccessGranted struct {
	UID     string  `json:"uid"`
	LpaType string  `json:"lpaType"`
	Actors  []Actor `json:"actors"`
}

func (h *MakeRegisterEventHandler) EventHandler(ctx context.Context, factory Factory, record *events.CloudWatchEvent) error {
	var data lpaAccessGranted

	dynamodbClient := factory.DynamoClient()

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

		if err := handleUsers(ctx, dynamodbClient, &actor); err != nil {
			logger.ErrorContext(
				ctx,
				err.Error(),
				slog.Group("location",
					slog.String("file", "makeregister_event_handler.go"),
				),
			)
			return err

		}

		if err := handleLpas(ctx, dynamodbClient, actor, data.UID); err != nil {
			return err
		}
	}

	return nil
}

func handleUsers(ctx context.Context, dynamoClient DynamodbClient, actor *Actor) error {
	var existingUser Actor

	err := dynamoClient.OneByUID(ctx, actor.SubjectID, &existingUser)

	if err != nil {
		return fmt.Errorf("Failed to find existing user %s: %w", actor.ActorUID, err)
	}

	if existingUser.userId != "" {
		actor.userId = existingUser.userId
		return nil
	}

	userId := uuid.New().String()
	newUser := map[string]types.AttributeValue{
		"Id":       &types.AttributeValueMemberS{Value: userId},
		"Identity": &types.AttributeValueMemberS{Value: actor.SubjectID},
	}

	err = dynamoClient.Put(ctx, actorUserTable, newUser)
	if err != nil {
		return fmt.Errorf("Failed to put user %s: %w", actor.ActorUID, err)
	}

	actor.userId = userId

	return nil
}

func handleLpas(ctx context.Context, dynamoClient DynamodbClient, actor Actor, LpaId string) error {
	lpaExists, err := dynamoClient.ExistsLpaIDAndUserID(ctx, LpaId, actor.userId)

	if err == nil && lpaExists == true {
		return nil
	}

	if err != nil {
		return fmt.Errorf("Failed to find existing LPA %s: %w", LpaId, err)
	}

	newLPA := map[string]types.AttributeValue{
		"Id":      &types.AttributeValueMemberS{Value: uuid.New().String()},
		"LpaUid":  &types.AttributeValueMemberS{Value: LpaId},
		"ActorId": &types.AttributeValueMemberS{Value: actor.ActorUID},
		"Added":   &types.AttributeValueMemberS{Value: time.Now().Format(time.RFC3339)},
		"UserId":  &types.AttributeValueMemberS{Value: actor.userId},
		"Comment": &types.AttributeValueMemberS{Value: "LPA added by Event Receiver"},
	}

	err = dynamoClient.Put(ctx, actorMapTable, newLPA)
	if err != nil {
		return fmt.Errorf("Failed to insert LPA mapping for user %s: %w", LpaId, err)
	}

	return nil
}
