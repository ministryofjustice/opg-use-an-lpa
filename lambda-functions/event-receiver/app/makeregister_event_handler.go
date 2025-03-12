package main

import (
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"github.com/aws/aws-lambda-go/events"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/ministryofjustice/opg-use-an-lpa/internal/dynamo"
	"log/slog"
	"time"

	"github.com/google/uuid"
)

type MakeRegisterEventHandler struct{}

type Actor struct {
	ActorUID  string `json:"actorUid"`
	SubjectID string `json:"subjectId"`
	Id        string
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

	err := dynamoClient.OneByIdentity(ctx, actor.SubjectID, &existingUser)

	if err != nil && !errors.Is(err, dynamo.NotFoundError{}) {
		return err
	}

	if existingUser.Id != "" {
		actor.Id = existingUser.Id
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

	actor.Id = userId

	return nil
}

func handleLpas(ctx context.Context, dynamoClient DynamodbClient, actor Actor, LpaUid string) error {
	lpaExists, err := dynamoClient.ExistsLpaIDAndUserID(ctx, LpaUid, actor.Id)

	if err == nil && lpaExists == true {
		return nil
	}

	if err != nil {
		return fmt.Errorf("Failed to find existing LPA %s: %w", LpaUid, err)
	}

	newLPA := map[string]types.AttributeValue{
		"Id":      &types.AttributeValueMemberS{Value: uuid.New().String()},
		"LpaUid":  &types.AttributeValueMemberS{Value: LpaUid},
		"ActorId": &types.AttributeValueMemberS{Value: actor.ActorUID},
		"Added":   &types.AttributeValueMemberS{Value: time.Now().Format(time.RFC3339)},
		"UserId":  &types.AttributeValueMemberS{Value: actor.Id},
		"Comment": &types.AttributeValueMemberS{Value: "LPA added by Event Receiver"},
	}

	err = dynamoClient.Put(ctx, actorMapTable, newLPA)
	if err != nil {
		return fmt.Errorf("Failed to insert LPA mapping for user %s: %w", LpaUid, err)
	}

	logger.InfoContext(
		ctx,
		"Added LPA mapping for user",
	)

	return nil
}
