package dynamo

import (
	"context"
	"errors"
	"fmt"
	"os"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
)

const (
	lpaUIDIndex = "LpaUIDIndex"
)

var (
	envPrefix         = os.Getenv("ENVIRONMENT")
	actorMapTable     = "UserLpaActorMap"
	actorMapUserIndex = "UserIndex"
	actorUserTable    = "ActorUsers"
	actorUserIndex    = "IdentityIndex"
)

type ActorUserMap struct {
	userId string
	lpaUid string
}

type DynamoDB interface {
	Query(context.Context, *dynamodb.QueryInput, ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
	GetItem(context.Context, *dynamodb.GetItemInput, ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error)
	PutItem(context.Context, *dynamodb.PutItemInput, ...func(*dynamodb.Options)) (*dynamodb.PutItemOutput, error)
}

type Client struct {
	svc DynamoDB
}

type NotFoundError struct{}

func (n NotFoundError) Error() string {
	return "No results found"
}

type ConditionalCheckFailedError struct{}

func (c ConditionalCheckFailedError) Error() string {
	return "Conditional checks failed"
}

func NewClient(cfg aws.Config) (*Client, error) {
	return &Client{svc: dynamodb.NewFromConfig(cfg)}, nil
}

func (c *Client) OneByIdentity(ctx context.Context, subjectId string, v interface{}) error {
	tableName := fmt.Sprintf("%s-%s", envPrefix, actorUserTable)
	response, err := c.svc.Query(ctx, &dynamodb.QueryInput{
		TableName: aws.String(tableName),
		IndexName: aws.String(actorUserIndex),
		ExpressionAttributeNames: map[string]string{
			"#Identity": "Identity",
		},
		ExpressionAttributeValues: map[string]types.AttributeValue{
			":Identity": &types.AttributeValueMemberS{Value: subjectId},
		},
		KeyConditionExpression: aws.String("#Identity = :Identity"),
		Limit:                  aws.Int32(1),
	})
	if err != nil {
		return fmt.Errorf("failed to query Identity: %w", err)
	}
	if len(response.Items) == 0 {
		return NotFoundError{}
	}

	return attributevalue.UnmarshalMap(response.Items[0], v)
}

func (c *Client) Put(ctx context.Context, tableName string, item map[string]types.AttributeValue) error {
	tableName = fmt.Sprintf("%s-"+tableName, envPrefix)

	input := &dynamodb.PutItemInput{
		TableName: aws.String(tableName),
		Item:      item,
	}

	_, err := c.svc.PutItem(ctx, input)

	if err != nil {
		var ccf *types.ConditionalCheckFailedException
		if errors.As(err, &ccf) {
			return ConditionalCheckFailedError{}
		}

		return err
	}

	return nil
}

func (c *Client) ExistsLpaIDAndUserID(ctx context.Context, lpaId string, userId string) (bool, error) {
	tableName := fmt.Sprintf("%s-%s", envPrefix, actorMapTable)
	response, err := c.svc.Query(ctx, &dynamodb.QueryInput{
		TableName: aws.String(tableName),
		IndexName: aws.String(actorMapUserIndex),
		ExpressionAttributeNames: map[string]string{
			"#UserId": "UserId",
		},
		ExpressionAttributeValues: map[string]types.AttributeValue{
			":userid": &types.AttributeValueMemberS{Value: userId},
		},
		KeyConditionExpression: aws.String("#UserId = :userid"),
	})
	if err != nil {
		return false, fmt.Errorf("failed to query LPA mappings: %w", err)
	}

	fmt.Printf("%+v\n", response)

	if response.Count > 0 {
		results := []ActorUserMap{}

		err = attributevalue.UnmarshalListOfMaps(response.Items, &results)
		if err != nil {
			return false, err
		}

		fmt.Printf("result: %+v\n", results)

		for _, item := range results {
			if item.lpaUid == lpaId {
				return true, nil
			}
		}
	}

	return false, nil
}
