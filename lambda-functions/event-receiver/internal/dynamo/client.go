package dynamo

import (
	"context"
	"errors"
	"fmt"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
)

const (
	lpaUIDIndex = "LpaUIDIndex"
)

type DynamoDB interface {
	Query(context.Context, *dynamodb.QueryInput, ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
	GetItem(context.Context, *dynamodb.GetItemInput, ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error)
	PutItem(context.Context, *dynamodb.PutItemInput, ...func(*dynamodb.Options)) (*dynamodb.PutItemOutput, error)
}

type Client struct {
	table string
	svc   DynamoDB
}

type NotFoundError struct{}

func (n NotFoundError) Error() string {
	return "No results found"
}

type ConditionalCheckFailedError struct{}

func (c ConditionalCheckFailedError) Error() string {
	return "Conditional checks failed"
}

func NewClient(cfg aws.Config, tableName string) (*Client, error) {
	return &Client{table: tableName, svc: dynamodb.NewFromConfig(cfg)}, nil
}

func (c *Client) OneByUID(ctx context.Context, subjectId string, v interface{}) error {
	response, err := c.svc.Query(ctx, &dynamodb.QueryInput{
		TableName: aws.String(c.table),
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

func (c *Client) Put(ctx context.Context, v interface{}) error {
	item, err := attributevalue.MarshalMap(v)
	if err != nil {
		return err
	}

	input := &dynamodb.PutItemInput{
		TableName: aws.String(c.table),
		Item:      item,
	}

	_, err = c.svc.PutItem(ctx, input)

	if err != nil {
		var ccf *types.ConditionalCheckFailedException
		if errors.As(err, &ccf) {
			return ConditionalCheckFailedError{}
		}

		return err
	}

	return nil
}

func (c *Client) GetByLpaIDAndUserID(ctx context.Context, lpaId string, userId string, v interface{}) error {
	response, err := c.svc.Query(ctx, &dynamodb.QueryInput{
		TableName: aws.String(c.table),
		ExpressionAttributeNames: map[string]string{
			"#LpaUid": "LpaUid",
			"#UserId": "UserId",
		},
		ExpressionAttributeValues: map[string]types.AttributeValue{
			":lpaUid": &types.AttributeValueMemberS{Value: lpaId},
			":userid": &types.AttributeValueMemberS{Value: userId},
		},
		KeyConditionExpression: aws.String("#LpaUid = :lpaUid AND #UserId = :userid"),
		Limit:                  aws.Int32(1),
	})
	if err != nil {
		return fmt.Errorf("failed to query LPA mappings: %w", err)
	}
	if len(response.Items) == 0 {
		return NotFoundError{}
	}

	return attributevalue.UnmarshalMap(response.Items[0], v)
}
