package dynamo

import (
	"context"
	"errors"
	"fmt"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/google/uuid"
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
	ctx             = context.Background()
	expectedError   = errors.New("err")
	subjectID       = "urn:fdc:gov.uk:2022:XXXX-XXXXXX"
	actorUid        = "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d"
	LpaUid          = "M-1234-5678-9012"
	userId          = uuid.New().String()
	UserLpaActorMap = uuid.New().String()
)

func TestOneByIdentity(t *testing.T) {
	mockDynamoDB := new(mocks.DynamoDB)

	expectedItem := map[string]types.AttributeValue{
		"subjectId": &types.AttributeValueMemberS{Value: subjectID},
		"Id":        &types.AttributeValueMemberS{Value: actorUid},
	}
	mockDynamoDB.On("Query", ctx, mock.Anything).
		Return(&dynamodb.QueryOutput{
			Items: []map[string]types.AttributeValue{expectedItem},
		}, nil).Once()

	c := &Client{svc: mockDynamoDB}

	var v map[string]any
	err := c.OneByIdentity(ctx, subjectID, &v)

	assert.Nil(t, err)
	assert.Equal(t, map[string]any{"Id": actorUid, "subjectId": subjectID}, v)
	mockDynamoDB.AssertExpectations(t)
}

func TestOneByIdentityWhenQueryError(t *testing.T) {
	mockDynamoDB := new(mocks.DynamoDB)

	mockDynamoDB.On("Query", ctx, mock.Anything).
		Return(&dynamodb.QueryOutput{}, expectedError).Once()

	c := &Client{svc: mockDynamoDB}

	err := c.OneByIdentity(ctx, subjectID, mock.Anything)

	assert.Equal(t, fmt.Errorf("failed to query Identity: %w", expectedError), err)
}

func TestOneByIdentityWhenNoItems(t *testing.T) {
	mockDynamoDB := new(mocks.DynamoDB)

	mockDynamoDB.On("Query", ctx, mock.Anything).
		Return(&dynamodb.QueryOutput{}, nil).Once()

	c := &Client{svc: mockDynamoDB}

	err := c.OneByIdentity(ctx, subjectID, mock.Anything)
	assert.ErrorIs(t, err, NotFoundError{})
}

func TestOneByIdentityWhenUnmarshalError(t *testing.T) {
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

	c := &Client{svc: mockDynamoDB}

	err := c.OneByIdentity(ctx, subjectID, "not an lpa")

	assert.IsType(t, &attributevalue.InvalidUnmarshalError{}, err)
	mockDynamoDB.AssertExpectations(t)
}

func TestPut(t *testing.T) {
	tests := []struct {
		name      string
		tableName string
		item      map[string]types.AttributeValue
		mockErr   error
		wantErr   bool
	}{
		{
			name:      "Valid Input",
			tableName: "ActorUsers",
			item: map[string]types.AttributeValue{
				"UserId": &types.AttributeValueMemberS{Value: userId},
				"LpaUid": &types.AttributeValueMemberS{Value: LpaUid},
			},
			mockErr: nil,
			wantErr: false,
		},
		{
			name:      "Empty Input",
			tableName: "ActorUsers",
			item:      map[string]types.AttributeValue{},
			mockErr:   nil,
			wantErr:   false,
		},
	}

	for _, tc := range tests {
		t.Run(tc.name, func(t *testing.T) {
			mockDynamoDB := new(mocks.DynamoDB)

			mockDynamoDB.On("PutItem", ctx, &dynamodb.PutItemInput{
				TableName: aws.String("ActorUsers"),
				Item:      tc.item,
			}).Return(&dynamodb.PutItemOutput{}, tc.mockErr).Once()

			c := &Client{svc: mockDynamoDB}

			err := c.Put(ctx, "ActorUsers", tc.item)

			if tc.wantErr && err == nil {
				t.Fatalf("%s: expected an error, but got none", tc.name)
			}
			if !tc.wantErr && err != nil {
				t.Fatalf("%s: did not expect an error, but got %v", tc.name, err)
			}

			mockDynamoDB.AssertExpectations(t)
		})
	}
}

func TestPutWhenError(t *testing.T) {
	expectedError := errors.New("put error")

	mockDynamoDB := new(mocks.DynamoDB)

	mockDynamoDB.On("PutItem", ctx, mock.Anything, mock.Anything).
		Return(nil, expectedError).Once()

	c := &Client{svc: mockDynamoDB}

	err := c.Put(ctx, "hello", map[string]types.AttributeValue{})
	assert.Equal(t, expectedError, err)
	mockDynamoDB.AssertExpectations(t)
}

func TestPutWhenUnmarshalError(t *testing.T) {
	mockDynamoDB := new(mocks.DynamoDB)

	mockDynamoDB.On("PutItem", ctx, mock.Anything, mock.Anything).
		Return(nil, errors.New("unmarshal error")).Once()

	c := &Client{svc: mockDynamoDB}

	err := c.Put(ctx, "ActorUsers", map[string]types.AttributeValue{
		"subjectId": &types.AttributeValueMemberS{Value: subjectID},
	})

	assert.NotNil(t, err)
	assert.Contains(t, err.Error(), "unmarshal error")

	mockDynamoDB.AssertExpectations(t)
}

func TestExistsLpaIDAndUserID(t *testing.T) {
	mockDynamoDB := new(mocks.DynamoDB)

	expectedItem := map[string]types.AttributeValue{
		"Id":      &types.AttributeValueMemberS{Value: UserLpaActorMap},
		"LpaUid":  &types.AttributeValueMemberS{Value: LpaUid},
		"ActorId": &types.AttributeValueMemberS{Value: actorUid},
		"UserId":  &types.AttributeValueMemberS{Value: userId},
	}

	mockDynamoDB.On("Query", ctx, mock.Anything).
		Return(&dynamodb.QueryOutput{
			Count: 1,
			Items: []map[string]types.AttributeValue{expectedItem},
		}, nil).Once()

	c := &Client{svc: mockDynamoDB}

	lpaExists, err := c.ExistsLpaIDAndUserID(ctx, LpaUid, userId)
	assert.Equal(t, true, lpaExists)
	assert.Nil(t, err)
	mockDynamoDB.AssertExpectations(t)
}

func TestExistsLpaIDAndUserIDWhenQueryError(t *testing.T) {
	mockDynamoDB := new(mocks.DynamoDB)

	mockDynamoDB.On("Query", ctx, mock.Anything).
		Return(&dynamodb.QueryOutput{}, expectedError).Once()

	c := &Client{svc: mockDynamoDB}

	_, err := c.ExistsLpaIDAndUserID(ctx, LpaUid, userId)

	assert.Equal(t, fmt.Errorf("failed to query LPA mappings: %w", expectedError), err)
}

func TestExistsLpaIDAndUserIDWhenNoItems(t *testing.T) {
	mockDynamoDB := new(mocks.DynamoDB)

	mockDynamoDB.On("Query", ctx, mock.Anything).
		Return(&dynamodb.QueryOutput{}, nil).Once()

	c := &Client{svc: mockDynamoDB}

	lpaExists, err := c.ExistsLpaIDAndUserID(ctx, LpaUid, userId)
	assert.ErrorIs(t, err, nil)
	assert.Equal(t, false, lpaExists)
}
