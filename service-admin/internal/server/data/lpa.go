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

type LPA struct {
	SiriusUID  string `json:"SiriusUid"`
	Added      string
	ActivateBy int
}

const (
	UserLpaActorTableName     = "UserLpaActorMap"
	UserLpaActorUserIndexName = "UserIndex"
)

var ErrUserLpaActorMapNotFound = errors.New("userlpaactormap not found")

func GetLpasByUserID(ctx context.Context, db *dynamodb.Client, uid string) (lpas []*LPA, err error) {
	result, err := db.Query(ctx, &dynamodb.QueryInput{
		TableName:              aws.String(prefixedTableName(UserLpaActorTableName)),
		IndexName:              aws.String(UserLpaActorUserIndexName),
		KeyConditionExpression: aws.String("UserId = :u"),
		ExpressionAttributeValues: map[string]types.AttributeValue{
			":u": &types.AttributeValueMemberS{Value: uid},
		},
	})
	if err != nil {
		log.Error().Err(err).Msg("error whilst searching for userId")
	}

	if result.Count > 0 {
		err = attributevalue.UnmarshalListOfMaps(result.Items, &lpas)
		if err != nil {
			log.Error().Err(err).Msg("unable to convert dynamo result into ActorUser")
		}

		return lpas, nil
	}

	return nil, ErrUserLpaActorMapNotFound
}
