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

type accountService struct {
	db *dynamodb.Client
}

type ActorUser struct {
	ID              string `json:"Id"`
	Email           string
	ActivationToken string
	ExpiresTTL      int
	LastLogin       string

	LPAs []*LPA
}

const (
	ActorTableName           = "ActorUsers"
	ActorTableEmailIndexName = "EmailIndex"
)

var ErrActorUserNotFound = errors.New("actoruser not found")

func NewAccountService(db *dynamodb.Client) *accountService {
	return &accountService{db: db}
}

func (a *accountService) GetActorUserByEmail(ctx context.Context, email string) (aa *ActorUser, err error) {
	result, err := a.db.Query(ctx, &dynamodb.QueryInput{
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

func (a *accountService) GetEmailByUserID(ctx context.Context, userID string) (email string, err error) {
	result, err := a.db.GetItem(ctx, &dynamodb.GetItemInput{
		TableName: aws.String(prefixedTableName(ActorTableName)),
		Key: map[string]types.AttributeValue{
			"Id": &types.AttributeValueMemberS{Value: userID},
		},
	})

	if err != nil {
		log.Error().Err(err).Msg("Error trying to get email by userID")
	}

	if result != nil {
		marshalledResult := ActorUser{}

		err = attributevalue.UnmarshalMap(result.Item, &marshalledResult)
		if err != nil {
			log.Error().Err(err).Msg("unable to convert dynamo result into ActorUser")
		}

		// we'll only ever want the one result
		return marshalledResult.Email, nil
	}

	return "", ErrActorUserNotFound
}
