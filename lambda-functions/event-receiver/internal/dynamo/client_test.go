package dynamo

import (
	"context"
	"errors"
	"fmt"
	"testing"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/aws/smithy-go"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
)

type testPK string

func (k testPK) PK() string { return string(k) }

type testSK string

func (k testSK) SK() string { return string(k) }

var (
	ctx           = context.Background()
	expectedError = errors.New("err")
)

func TestOne(t *testing.T) {
	expected := map[string]string{"Col": "Val"}
	pkey, _ := attributevalue.Marshal("a-pk")
	skey, _ := attributevalue.Marshal("a-sk")
	data, _ := attributevalue.MarshalMap(expected)

	dynamoDB := newMockDynamoDB(t)
	dynamoDB.EXPECT().
		GetItem(ctx, &dynamodb.GetItemInput{
			TableName: aws.String("this"),
			Key:       map[string]types.AttributeValue{"PK": pkey, "SK": skey},
		}).
		Return(&dynamodb.GetItemOutput{Item: data}, nil)

	c := &Client{table: "this", svc: dynamoDB}

	var actual map[string]string
	err := c.One(ctx, testPK("a-pk"), testSK("a-sk"), &actual)
	assert.Nil(t, err)
	assert.Equal(t, expected, actual)
}

func TestOneWhenError(t *testing.T) {
	pkey, _ := attributevalue.Marshal("a-pk")
	skey, _ := attributevalue.Marshal("a-sk")

	dynamoDB := newMockDynamoDB(t)
	dynamoDB.EXPECT().
		GetItem(ctx, &dynamodb.GetItemInput{
			TableName: aws.String("this"),
			Key:       map[string]types.AttributeValue{"PK": pkey, "SK": skey},
		}).
		Return(&dynamodb.GetItemOutput{}, expectedError)

	c := &Client{table: "this", svc: dynamoDB}

	var v string
	err := c.One(ctx, testPK("a-pk"), testSK("a-sk"), &v)
	assert.Equal(t, expectedError, err)
	assert.Equal(t, "", v)
}

func TestOneWhenNotFound(t *testing.T) {
	pkey, _ := attributevalue.Marshal("a-pk")
	skey, _ := attributevalue.Marshal("a-sk")

	dynamoDB := newMockDynamoDB(t)
	dynamoDB.EXPECT().
		GetItem(ctx, &dynamodb.GetItemInput{
			TableName: aws.String("this"),
			Key:       map[string]types.AttributeValue{"PK": pkey, "SK": skey},
		}).
		Return(&dynamodb.GetItemOutput{}, nil)

	c := &Client{table: "this", svc: dynamoDB}

	var v string
	err := c.One(ctx, testPK("a-pk"), testSK("a-sk"), &v)
	assert.Equal(t, NotFoundError{}, err)
	assert.Equal(t, "", v)
}

func TestOneByUID(t *testing.T) {
	dynamoDB := newMockDynamoDB(t)
	dynamoDB.EXPECT().
		Query(ctx, &dynamodb.QueryInput{
			TableName:                aws.String("this"),
			IndexName:                aws.String(lpaUIDIndex),
			ExpressionAttributeNames: map[string]string{"#LpaUID": "LpaUID", "#PK": "PK", "#SK": "SK"},
			ExpressionAttributeValues: map[string]types.AttributeValue{
				":LpaUID": &types.AttributeValueMemberS{Value: "M-1111-2222-3333"},
				":PK":     &types.AttributeValueMemberS{Value: LpaKey("").PK()},
				":SK":     &types.AttributeValueMemberS{Value: DonorKey("").SK()},
			},
			KeyConditionExpression: aws.String("#LpaUID = :LpaUID"),
			FilterExpression:       aws.String("begins_with(#PK, :PK) and begins_with(#SK, :SK)"),
			Limit:                  aws.Int32(1),
		}).
		Return(&dynamodb.QueryOutput{
			Items: []map[string]types.AttributeValue{{
				"PK":     &types.AttributeValueMemberS{Value: "LPA#123"},
				"LpaUID": &types.AttributeValueMemberS{Value: "M-1111-2222-3333"},
			}},
		}, nil)

	c := &Client{table: "this", svc: dynamoDB}

	var v map[string]any
	err := c.OneByUID(ctx, "M-1111-2222-3333", &v)

	assert.Nil(t, err)
	assert.Equal(t, map[string]any{"PK": "LPA#123", "LpaUID": "M-1111-2222-3333"}, v)
}

func TestOneByUIDWhenQueryError(t *testing.T) {
	dynamoDB := newMockDynamoDB(t)
	dynamoDB.EXPECT().
		Query(ctx, mock.Anything).
		Return(&dynamodb.QueryOutput{}, expectedError)

	c := &Client{table: "this", svc: dynamoDB}

	err := c.OneByUID(ctx, "M-1111-2222-3333", mock.Anything)

	assert.Equal(t, fmt.Errorf("failed to query UID: %w", expectedError), err)
}

func TestOneByUIDWhenNoItems(t *testing.T) {
	dynamoDB := newMockDynamoDB(t)
	dynamoDB.EXPECT().
		Query(ctx, mock.Anything).
		Return(&dynamodb.QueryOutput{}, nil)

	c := &Client{table: "this", svc: dynamoDB}

	err := c.OneByUID(ctx, "M-1111-2222-3333", mock.Anything)
	assert.ErrorIs(t, err, NotFoundError{})
}

func TestOneByUIDWhenUnmarshalError(t *testing.T) {
	dynamoDB := newMockDynamoDB(t)
	dynamoDB.EXPECT().
		Query(ctx, mock.Anything).
		Return(&dynamodb.QueryOutput{
			Items: []map[string]types.AttributeValue{
				{
					"PK":     &types.AttributeValueMemberS{Value: "LPA#123"},
					"LpaUID": &types.AttributeValueMemberS{Value: "M-1111-2222-3333"},
				},
			},
		}, nil)

	c := &Client{table: "this", svc: dynamoDB}

	err := c.OneByUID(ctx, "M-1111-2222-3333", "not an lpa")

	assert.IsType(t, &attributevalue.InvalidUnmarshalError{}, err)
}

func TestPut(t *testing.T) {
	testCases := map[string]map[string]string{
		"Without UpdatedAt": {"Col": "Val"},
		"Zero UpdatedAt":    {"Col": "Val", "UpdatedAt": "0001-01-01T00:00:00Z"},
	}

	for name, dataMap := range testCases {
		t.Run(name, func(t *testing.T) {
			data, _ := attributevalue.MarshalMap(dataMap)

			dynamoDB := newMockDynamoDB(t)
			dynamoDB.EXPECT().
				PutItem(ctx, &dynamodb.PutItemInput{
					TableName: aws.String("this"),
					Item:      data,
				}).
				Return(&dynamodb.PutItemOutput{}, nil)

			c := &Client{table: "this", svc: dynamoDB}

			err := c.Put(ctx, dataMap)
			assert.Nil(t, err)
		})
	}
}

func TestPutWhenStructHasVersion(t *testing.T) {
	data, _ := attributevalue.MarshalMap(map[string]any{"Col": "Val", "Version": 2})

	dynamoDB := newMockDynamoDB(t)
	dynamoDB.EXPECT().
		PutItem(ctx, &dynamodb.PutItemInput{
			TableName:                 aws.String("this"),
			Item:                      data,
			ConditionExpression:       aws.String("Version = :version"),
			ExpressionAttributeValues: map[string]types.AttributeValue{":version": &types.AttributeValueMemberN{Value: "1"}},
		}).
		Return(&dynamodb.PutItemOutput{}, nil)

	c := &Client{table: "this", svc: dynamoDB}

	err := c.Put(ctx, map[string]any{"Col": "Val", "Version": 1})
	assert.Nil(t, err)
}

func TestPutWhenConditionalCheckFailedException(t *testing.T) {
	data, _ := attributevalue.MarshalMap(map[string]any{"Col": "Val", "Version": 2})

	dynamoDB := newMockDynamoDB(t)
	dynamoDB.EXPECT().
		PutItem(ctx, &dynamodb.PutItemInput{
			TableName:                 aws.String("this"),
			Item:                      data,
			ConditionExpression:       aws.String("Version = :version"),
			ExpressionAttributeValues: map[string]types.AttributeValue{":version": &types.AttributeValueMemberN{Value: "1"}},
		}).
		Return(&dynamodb.PutItemOutput{}, &smithy.OperationError{Err: &types.ConditionalCheckFailedException{}})

	c := &Client{table: "this", svc: dynamoDB}

	err := c.Put(ctx, map[string]any{"Col": "Val", "Version": 1})
	assert.Equal(t, ConditionalCheckFailedError{}, err)
}

func TestPutWhenError(t *testing.T) {
	dynamoDB := newMockDynamoDB(t)
	dynamoDB.EXPECT().
		PutItem(ctx, mock.Anything).
		Return(&dynamodb.PutItemOutput{}, expectedError)

	c := &Client{table: "this", svc: dynamoDB}

	err := c.Put(ctx, "hello")
	assert.Equal(t, expectedError, err)
}

func TestPutWhenUnmarshalError(t *testing.T) {
	c := &Client{table: "this", svc: newMockDynamoDB(t)}

	err := c.Put(ctx, map[string]string{"Col": "Val", "Version": "not an int"})
	assert.NotNil(t, err)
}
