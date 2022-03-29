package data

import (
	"errors"

	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/service/dynamodb"
	"github.com/aws/aws-sdk-go/service/dynamodb/dynamodbattribute"
	"github.com/aws/aws-sdk-go/service/dynamodb/dynamodbiface"
	"github.com/rs/zerolog/log"
)

type LPA struct {
	SiriusUID string `json:"SiriusUid"`
	Added     string
	UserID    string `json:"UserId"`
}

type ActivationCodeLPAResult struct {
	SiriusUID string `json:"SiriusUid"`
}

const (
	UserLpaActorTableName     = "UserLpaActorMap"
	UserLpaActorUserIndexName = "UserIndex"
	ActivationCodeIndexName   = "ActivationCodeIndex"
)

var ErrUserLpaActorMapNotFound = errors.New("userlpaactormap not found")

func GetLpasByUserID(db dynamodbiface.DynamoDBAPI, uid string) (lpas []*LPA, err error) {
	result, err := db.Query(&dynamodb.QueryInput{
		TableName: aws.String(prefixedTableName(UserLpaActorTableName)),
		IndexName: aws.String(UserLpaActorUserIndexName),
		KeyConditions: map[string]*dynamodb.Condition{
			"UserId": {
				ComparisonOperator: aws.String("EQ"),
				AttributeValueList: []*dynamodb.AttributeValue{
					{
						S: aws.String(uid),
					},
				},
			},
		},
	})
	if err != nil {
		log.Error().Err(err).Msg("error whilst searching for userId")
	}

	if *result.Count > 0 {
		err = dynamodbattribute.UnmarshalListOfMaps(result.Items, &lpas)
		if err != nil {
			log.Error().Err(err).Msg("unable to convert dynamo result into ActorUser")
		}

		return lpas, nil
	}

	return nil, ErrUserLpaActorMapNotFound
}

func GetLPAByActivationCode(db dynamodbiface.DynamoDBAPI, activationCode string) (lpa *LPA, err error) {
	result, err := db.Query(&dynamodb.QueryInput{
		TableName: aws.String(prefixedTableName(UserLpaActorTableName)),
		IndexName: aws.String(ActivationCodeIndexName),
		KeyConditions: map[string]*dynamodb.Condition{
			"ActivationCode": {
				ComparisonOperator: aws.String("EQ"),
				AttributeValueList: []*dynamodb.AttributeValue{
					{
						S: aws.String(activationCode),
					},
				},
			},
		},
	})

	if err != nil {
		log.Error().Err(err).Msg("error while searching for activationCode")
	}

	if *result.Count > 0 {
		var lpas []*LPA

		err = dynamodbattribute.UnmarshalListOfMaps(result.Items, &lpas)
		if err != nil {
			log.Error().Err(err).Msg("unable to convert dynamo result into ActorUser")
		}

		return lpas[0], nil
	}

	return nil, ErrUserLpaActorMapNotFound
}
