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

type lpaService struct {
	db DynamoConnection
}

type LPA struct {
	SiriusUID    string `json:"SiriusUid"`
	Added        string
	UserID       string `json:"UserId"`
	ActivateBy   int
	ActivatedOn  string
}

const (
	UserLpaActorTableName     = "UserLpaActorMap"
	UserLpaActorUserIndexName = "UserIndex"
	ActivationCodeIndexName   = "ActivationCodeIndex"
	SiriusUIDIndex            = "SiriusUidIndex"
	CodesTableName            = "lpa-codes-local"
)

var ErrUserLpaActorMapNotFound = errors.New("userlpaactormap not found")

func NewLPAService(db DynamoConnection) *lpaService {
	return &lpaService{db: db}
}

func (l *lpaService) GetLpasByUserID(ctx context.Context, uid string) (lpas []*LPA, err error) {
	result, err := l.db.Client.Query(ctx, &dynamodb.QueryInput{
		TableName:              aws.String(l.db.prefixedTableName(UserLpaActorTableName)),
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

func (l *lpaService) GetLPAByActivationCode(ctx context.Context, activationCode string) (lpa *LPA, err error) {
	result, err := l.db.Client.Query(ctx, &dynamodb.QueryInput{
		TableName:              aws.String(l.db.prefixedTableName(UserLpaActorTableName)),
		IndexName:              aws.String(ActivationCodeIndexName),
		KeyConditionExpression: aws.String("ActivationCode = :a"),
		ExpressionAttributeValues: map[string]types.AttributeValue{
			":a": &types.AttributeValueMemberS{Value: activationCode},
		},
	})

	if err != nil {
		log.Error().Err(err).Msg("error while searching for activationCode")
	}

	if result.Count > 0 {
		var lpas []*LPA

		err = attributevalue.UnmarshalListOfMaps(result.Items, &lpas)
		if err != nil {
			log.Error().Err(err).Msg("unable to convert dynamo result into ActorUser")
		}

		return lpas[0], nil
	}

	return nil, ErrUserLpaActorMapNotFound
}

func (l *lpaService) GetLpaRecordBySiriusID(ctx context.Context, lpaNumber string) (lpas []*LPA, err error) {
	result, err := l.db.Client.Query(ctx, &dynamodb.QueryInput{
		TableName:              aws.String(l.db.prefixedTableName(UserLpaActorTableName)),
		IndexName:              aws.String(SiriusUIDIndex),
		KeyConditionExpression: aws.String("SiriusUid = :s"),
		ExpressionAttributeValues: map[string]types.AttributeValue{
			":s": &types.AttributeValueMemberS{Value: lpaNumber},
		},
	})

	if err != nil {
		log.Error().Err(err).Msg("error while searching for LpaNumber")
	}

	if result.Count > 0 {
		err = attributevalue.UnmarshalListOfMaps(result.Items, &lpas)
		if err != nil {
			log.Error().Err(err).Msg("unable to convert dynamo result into LPA")
		} else {
			return lpas, nil
		}
	}

	return nil, ErrUserLpaActorMapNotFound
}
