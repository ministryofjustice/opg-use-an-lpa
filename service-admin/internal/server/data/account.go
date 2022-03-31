package data

import (
	"context"
	"errors"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/rs/zerolog/log"
)

type ActorUser struct {
	ID              string `json:"Id"`
	Email           string
	ActivationToken string
	ExpiresTTL      int
	LastLogin       string

	LPAs []*LPA
}

type AccountController interface {
}

const (
	ActorTableName           = "ActorUsers"
	ActorTableEmailIndexName = "EmailIndex"
)

var ErrActorUserNotFound = errors.New("actoruser not found")

func GetActorUserByEmail(ctx context.Context, db *dynamodb.Client, email string) (aa *ActorUser, err error) {
	result, err := db.Query(ctx, &dynamodb.QueryInput{
		TableName:              aws.String(prefixedTableName(ActorTableName)),
		IndexName:              aws.String(ActorTableEmailIndexName),
		KeyConditionExpression: aws.String("Email = :e"),
		ExpressionAttributeValues: map[string]types.AttributeValue{
			":e": &types.AttributeValueMemberS{Value: email},
		},
	})
	if err != nil {
		log.Error().Err(err).Msg("error whilst searching for email")
	}

	if result.Count > 0 {
		results := []ActorUser{}

		err = attributevalue.UnmarshalListOfMaps(result.Items, &results)
		if err != nil {
			log.Error().Err(err).Msg("unable to convert dynamo result into ActorUser")
		}

		// we'll only ever want the one result
		return &results[0], nil
	}

	return nil, ErrActorUserNotFound
}

func GetEmailByUserID(db dynamodbiface.DynamoDBAPI, userID string) (email string, err error) {
	result, err := db.GetItem(&dynamodb.GetItemInput{
		TableName: aws.String(prefixedTableName(ActorTableName)),
		Key: map[string]*dynamodb.AttributeValue{
			"Id": {
				S: aws.String(userID),
			},
		},
	})

	if err != nil {
		log.Error().Err(err).Msg("Error trying to get email by userID")
	}

	if result != nil {
		marshalledResult := ActorUser{}

		err = dynamodbattribute.UnmarshalMap(result.Item, &marshalledResult)
		if err != nil {
			log.Error().Err(err).Msg("unable to convert dynamo result into ActorUser")
		}

		// we'll only ever want the one result
		return marshalledResult.Email, nil
	}

	return "", ErrActorUserNotFound
}
