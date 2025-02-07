//go:generate mockery --all --recursive
package main

import (
	"context"
	"github.com/ministryofjustice/opg-use-an-lpa/app/mocks"
	"github.com/stretchr/testify/mock"
	"encoding/json"
	"github.com/ministryofjustice/opg-go-common/telemetry"
	"testing"
	"time"

	"github.com/aws/aws-lambda-go/events"
	"github.com/stretchr/testify/assert"
)

var (
	payload = map[string]interface{}{
		"uid":     "M-1234-5678-9012",
		"lpaType": "personal-welfare",
		"actors": []map[string]string{
			{
				"actorUid":  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
				"subjectId": "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
			},
			{
				"actorUid":  "eda719db-8880-4dda-8c5d-bb9ea12c236f",
				"subjectId": "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
			},
		},
	}
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

	mockDynamo := new(mocks.DynamoClient)
	mockFactory := new(mocks.Factory)
	mockFactory.On("DynamoClient").Return(mockDynamo)

	mockDynamo.On("OneByUID", ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).Return(nil)
	mockDynamo.On("Put", ctx, mock.Anything).Return(nil)

	err := handler.Handle(ctx, sqsMessage, mockFactory)

	assert.NoError(t, err)

	mockDynamo.AssertExpectations(t)
	mockFactory.AssertExpectations(t)

}


func TestMakeRegisterEventHandlerLpaAccessGranted(t *testing.T) {
	ctx := context.Background()

	cloudWatchPayload, err := json.Marshal(payload)
	assert.NoError(t, err)

	cloudWatchMessage := &events.CloudWatchEvent{
		ID:         "1",
		DetailType: "lpa-access-granted",
		Source:     "opg.poas.makeregister",
		AccountID:  "123",
		Time:       time.Now(),
		Region:     "us-east-1",
		Resources:  []string{},
		Detail:     json.RawMessage(cloudWatchPayload),
	}

	sqsBody, err := json.Marshal(cloudWatchMessage)
	assert.NoError(t, err)

	sqsEvent := events.SQSEvent{
		Records: []events.SQSMessage{
			{
				MessageId: "1",
				Body:      string(sqsBody),
			},
		},
	}
	logger = telemetry.NewLogger("opg-use-an-lpa/event-receiver")

	result, err := handler(ctx, sqsEvent)

	assert.NoError(t, err)
	assert.Empty(t, result["batchItemFailures"])
}

func TestHandleSQSEvent_UnmarshalFailure(t *testing.T) {
	ctx := context.Background()

	malformedBody := `{
		"uid":     "M-1234-5678-9012"
		"lpaType": "personal-welfare"
	}`

	sqsEvent := events.SQSEvent{
		Records: []events.SQSMessage{
			{
				MessageId: "1",
				Body:      malformedBody,
			},
		},
	}

	logger = telemetry.NewLogger("opg-use-an-lpa/event-receiver")

	result, err := handler(ctx, sqsEvent)
	assert.Error(t, err)
	assert.Len(t, result["batchItemFailures"], 1)
}

func TestHandleCloudWatchEvent_MissingDetailType(t *testing.T) {
	ctx := context.Background()

	cloudWatchPayload, err := json.Marshal(payload)
	assert.NoError(t, err)

	cloudWatchMessage := &events.CloudWatchEvent{
		ID:         "1",
		DetailType: "hh",
		Source:     "opg.poas.makeregister",
		AccountID:  "123",
		Time:       time.Now(),
		Region:     "us-east-1",
		Resources:  []string{},
		Detail:     json.RawMessage(cloudWatchPayload),
	}

	sqsBody, err := json.Marshal(cloudWatchMessage)
	assert.NoError(t, err)

	sqsEvent := events.SQSEvent{
		Records: []events.SQSMessage{
			{
				MessageId: "1",
				Body:      string(sqsBody),
			},
		},
	}
	logger = telemetry.NewLogger("opg-use-an-lpa/event-receiver")

	result, err := handler(ctx, sqsEvent)

	assert.Error(t, err)
	assert.Equal(t, "Unhandled event type", err.Error())
	assert.Len(t, result["batchItemFailures"], 1)
}
