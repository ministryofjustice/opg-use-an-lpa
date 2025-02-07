package main

import (
	"context"
	"encoding/json"
	"github.com/aws/aws-lambda-go/events"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/ministryofjustice/opg-use-an-lpa/internal/random"
	"log/slog"
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

func (h *MakeRegisterEventHandler) EventHandler(ctx context.Context, record *events.CloudWatchEvent, factory *Factory) error {

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
		if err := handleUsers(ctx, actor, factory.DynamoClient()); err != nil {
			logger.ErrorContext(
				ctx,
				err.Error(),
				slog.Group("location",
					slog.String("file", "makeregister_event_handler.go"),
				),
			)
		}

		return nil
	}

	return nil
}

func handleUsers(ctx context.Context, actor Actor, dynamoClient DynamodbClient) error {
	// receive data, with data.ActorUID, using dynamo, try and get the row with that id from ActorUsers table and "identity" col
	// new entry to Actor users of Id (v4 guid) and identity

	var existingUser Actor

	err := dynamoClient.OneByUID(ctx, actor.SubjectID, &existingUser)

	if err != nil {
		logger.ErrorContext(
			ctx,
			"Failed to find existing user: "+actor.ActorUID+" - Error: "+err.Error(),
			slog.Group("location",
				slog.String("file", "makeregister_event_handler.go"),
			),
		)

		return err
	}

	newUser := map[string]types.AttributeValue{
		"Id":       &types.AttributeValueMemberS{Value: random.UuidString()},
		"Identity": &types.AttributeValueMemberS{Value: actor.SubjectID},
	}

	err = dynamoClient.Put(ctx, newUser)
	if err != nil {
		logger.ErrorContext(
			ctx,
			"Failed to put actor: "+actor.ActorUID+" - Error: "+err.Error(),
			slog.Group("location",
				slog.String("file", "makeregister_event_handler.go"),
			),
		)

		return err
	}

	return nil
}
