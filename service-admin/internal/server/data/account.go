package data

import (
	"errors"

	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/service/dynamodb"
	"github.com/aws/aws-sdk-go/service/dynamodb/dynamodbattribute"
	"github.com/aws/aws-sdk-go/service/dynamodb/dynamodbiface"
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

const (
	ActorTableName           = "ActorUsers"
	ActorTableEmailIndexName = "EmailIndex"
)

var ErrActorUserNotFound = errors.New("actoruser not found")

func GetActorUserByEmail(db dynamodbiface.DynamoDBAPI, email string) (aa *ActorUser, err error) {
	result, err := db.Query(&dynamodb.QueryInput{
		TableName: aws.String(ActorTableName),
		IndexName: aws.String(ActorTableEmailIndexName),
		KeyConditions: map[string]*dynamodb.Condition{
			"Email": {
				ComparisonOperator: aws.String("EQ"),
				AttributeValueList: []*dynamodb.AttributeValue{
					{
						S: aws.String(email),
					},
				},
			},
		},
	})
	if err != nil {
		log.Error().Err(err).Msg("error whilst searching for email")
	}

	if *result.Count > 0 {
		results := []ActorUser{}

		err = dynamodbattribute.UnmarshalListOfMaps(result.Items, &results)
		if err != nil {
			log.Error().Err(err).Msg("unable to convert dynamo result into ActorUser")
		}

		// we'll only ever want the one result
		return &results[0], nil
	}

	return nil, ErrActorUserNotFound
}
