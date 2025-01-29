package main

import (
	"context"
	"encoding/json"
	"fmt"
	"github.com/aws/aws-lambda-go/events"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
)

type makeregisterEventHandler struct{}

type Actor struct {
	ActorUID  string `json:"actorUid"`
	SubjectID string `json:"subjectId"`
}

type lpaAccessGranted struct {
	UID     string  `json:"uid"`
	LpaType string  `json:"lpaType"`
	Actors  []Actor `json:"actors"`
}

func (h *makeregisterEventHandler) Handle(ctx context.Context, record *events.SQSMessage, factory *Factory) error {

	var data lpaAccessGranted

	if err := json.Unmarshal([]byte(record.Body), &data); err != nil {
		return fmt.Errorf("failed to unmarshal SQS message: %w", err)
	}

	fmt.Printf("Successfully unmarshalled LPA Access Granted: %+v\n", data.UID)

	for _, actor := range data.Actors {
		fmt.Printf("Successfully unmarshalled LPA Access Granted: %+v\n", actor.ActorUID)

		if err := handleUsers(ctx, actor, factory.DynamoClient()); err != nil {
			fmt.Printf("could not find actor: %+v\n", data.Actors)
		}

		return nil
	}

	return nil
}

func handleUsers(ctx context.Context, actor Actor, dynamoClient *dynamodb.Client) error {
	// receive data, with data.ActorUID, using dynamo, try and get the row with that id from ActorUsers table and "identity" col
	// new entry to Actor users of Id (v4 guid) and identity
	getInput := &dynamodb.GetItemInput{
		TableName: aws.String(tableName),
		Key: map[string]types.AttributeValue{
			"Identity": &types.AttributeValueMemberS{Value: actor.ActorUID},
		},
	}

	result, err := dynamoClient.GetItem(ctx, getInput)
	if err != nil {
		return fmt.Errorf("failed to get row for user %s: %w", actor.ActorUID, err)
	}

	if result.Item != nil {
		fmt.Printf("User %s is already registered\n", actor.ActorUID)
	}

	putItem := map[string]types.AttributeValue{
		"Id":       &types.AttributeValueMemberS{Value: actor.ActorUID},
		"Identity": &types.AttributeValueMemberS{Value: actor.SubjectID},
	}

	putInput := &dynamodb.PutItemInput{
		TableName: &tableName,
		Item:      putItem,
	}

	_, err = dynamoClient.PutItem(ctx, putInput)
	if err != nil {
		return fmt.Errorf("failed to insert actor %s: %w", actor.ActorUID, err)
	}

	fmt.Printf("Successfully inserted actor: %s\n", actor.ActorUID)
	return nil
}
