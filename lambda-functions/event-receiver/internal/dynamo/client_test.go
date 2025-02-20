package dynamo

import (
	"context"
	"errors"
	"fmt"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/ministryofjustice/opg-use-an-lpa/internal/dynamo/mocks"
	"testing"

	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
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
	subjectID     = "urn:fdc:gov.uk:2022:XXXX-XXXXXX"
	actorUid      = "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d"
)

func TestOneByUID(t *testing.T) {
	mockDynamoDB := new(mocks.DynamoDB)

	expectedItem := map[string]types.AttributeValue{
		"subjectId": &types.AttributeValueMemberS{Value: subjectID},
		"Id":        &types.AttributeValueMemberS{Value: actorUid},
	}
	mockDynamoDB.On("Query", ctx, mock.Anything).
		Return(&dynamodb.QueryOutput{
			Items: []map[string]types.AttributeValue{expectedItem},
		}, nil).Once()

	c := &Client{table: "this", svc: mockDynamoDB}

	var v map[string]any
	err := c.OneByUID(ctx, subjectID, &v)

	assert.Nil(t, err)
	assert.Equal(t, map[string]any{"Id": actorUid, "subjectId": subjectID}, v)
	mockDynamoDB.AssertExpectations(t)
}

func TestOneByUIDWhenQueryError(t *testing.T) {
	mockDynamoDB := new(mocks.DynamoDB)

	mockDynamoDB.On("Query", ctx, mock.Anything).
		Return(&dynamodb.QueryOutput{}, expectedError).Once()

	c := &Client{table: "this", svc: mockDynamoDB}

	err := c.OneByUID(ctx, subjectID, mock.Anything)

	assert.Equal(t, fmt.Errorf("failed to query Identity: %w", expectedError), err)
}

func TestOneByUIDWhenNoItems(t *testing.T) {
	mockDynamoDB := new(mocks.DynamoDB)

	mockDynamoDB.On("Query", ctx, mock.Anything).
		Return(&dynamodb.QueryOutput{}, nil).Once()

	c := &Client{table: "this", svc: mockDynamoDB}

	err := c.OneByUID(ctx, subjectID, mock.Anything)
	assert.ErrorIs(t, err, NotFoundError{})
}

func TestOneByUIDWhenUnmarshalError(t *testing.T) {
	mockDynamoDB := new(mocks.DynamoDB)

	mockDynamoDB.On("Query", ctx, mock.Anything).
		Return(&dynamodb.QueryOutput{
			Items: []map[string]types.AttributeValue{
				{
					"Id":        &types.AttributeValueMemberS{Value: actorUid},
					"subjectId": &types.AttributeValueMemberS{Value: subjectID},
				},
			},
		}, nil).Once()

	c := &Client{table: "this", svc: mockDynamoDB}

	err := c.OneByUID(ctx, subjectID, "not an lpa")

	assert.IsType(t, &attributevalue.InvalidUnmarshalError{}, err)
	mockDynamoDB.AssertExpectations(t)
}

func TestPut(t *testing.T) {
	testCases := map[string]map[string]string{
		"Without UpdatedAt": {"subjectId": subjectID},
		"Zero UpdatedAt":    {"subjectId": subjectID, "UpdatedAt": "0001-01-01T00:00:00Z"},
	}

	for name, dataMap := range testCases {
		t.Run(name, func(t *testing.T) {
			data, err := attributevalue.MarshalMap(dataMap)
			assert.NoError(t, err)

			mockDynamoDB := new(mocks.DynamoDB)

			mockDynamoDB.On("PutItem", ctx, &dynamodb.PutItemInput{
				TableName: aws.String("this"),
				Item:      data,
			}).Return(&dynamodb.PutItemOutput{}, nil).Once()

			c := &Client{table: "this", svc: mockDynamoDB}

			err = c.Put(ctx, dataMap)
			assert.Nil(t, err)
		})
	}
}

func TestPutWhenError(t *testing.T) {
	expectedError := errors.New("put error")

	mockDynamoDB := new(mocks.DynamoDB)

	mockDynamoDB.On("PutItem", ctx, mock.Anything).
		Return(nil, expectedError).Once()

	c := &Client{table: "this", svc: mockDynamoDB}

	err := c.Put(ctx, "hello")
	assert.Equal(t, expectedError, err)
	mockDynamoDB.AssertExpectations(t)
}

func TestPutWhenUnmarshalError(t *testing.T) {
	mockDynamoDB := new(mocks.DynamoDB)

	mockDynamoDB.On("PutItem", ctx, mock.Anything).
		Return(nil, errors.New("unmarshal error")).Once()

	c := &Client{table: "this", svc: mockDynamoDB}

	err := c.Put(ctx, map[string]string{
		"subjectId": subjectID,
	})

	assert.NotNil(t, err)
	assert.Contains(t, err.Error(), "unmarshal error")

	mockDynamoDB.AssertExpectations(t)
}
