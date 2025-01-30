package main

import (
	"context"
	"github.com/stretchr/testify/mock"
	"testing"

	"github.com/aws/aws-lambda-go/events"
	"github.com/stretchr/testify/assert"
)

type MockDynamoDbClient struct {
	mock.Mock
}

func (m *MockDynamoDbClient) OneByUID(ctx context.Context, subjectID string, v interface{}) error {
	args := m.Called(ctx, subjectID, v)
	return args.Error(0)
}

func (m *MockDynamoDbClient) Put(ctx context.Context, v interface{}) error {
	args := m.Called(ctx, v)
	return args.Error(0)
}

type MockFactory struct {
	mock.Mock
}

func (m *MockFactory) DynamoClient() *DynamodbClient {
	return m.Called().Get(0).(*DynamodbClient)
}

func TestMakeRegisterEventHandler_Handle(t *testing.T) {
	ctx := context.Background()
	handler := &makeregisterEventHandler{}

	payload := `{
          "uid": "M-1234-5678-9012",
          "lpaType": "personal-welfare",
          "actors" : [
            {
              "actorUid": "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
              "subjectId": "urn:fdc:gov.uk:2022:XXXX-XXXXXX"
            },
            {
              "actorUid": "eda719db-8880-4dda-8c5d-bb9ea12c236f",
              "subjectId": "urn:fdc:gov.uk:2022:XXXX-XXXXXX"
            }
          ]
    }`

	sqsMessage := &events.SQSMessage{
		MessageId: "1",
		Body:      payload,
	}

	mockDynamo := new(MockDynamoDbClient)
	mockFactory := new(MockFactory)
	mockFactory.On("DynamoClient").Return(mockDynamo)

	mockDynamo.On("OneByUID", ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).Return(nil)
	mockDynamo.On("Put", ctx, mock.Anything).Return(nil)

	err := handler.Handle(ctx, sqsMessage, mockFactory)

	assert.NoError(t, err)

	mockDynamo.AssertExpectations(t)
	mockFactory.AssertExpectations(t)

}
