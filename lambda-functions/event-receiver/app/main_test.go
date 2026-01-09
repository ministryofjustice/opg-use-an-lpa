package main

import (
	"context"
	"encoding/json"
	"log/slog"
	"testing"
	"time"

	"github.com/aws/aws-lambda-go/events"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
)

var (
	ctx     = context.Background()
	payload = map[string]any{
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

func TestValidCloudWatchEvent(t *testing.T) {
	logger = slog.New(slog.DiscardHandler)
	lpaUID := "M-1234-5678-9012"

	cloudWatchPayload, err := json.Marshal(payload)
	assert.NoError(t, err)

	cloudWatchEvent := &events.CloudWatchEvent{
		ID:         "1",
		DetailType: "lpa-access-granted",
		Source:     "opg.poas.makeregister",
		AccountID:  "123",
		Time:       time.Now(),
		Region:     "us-east-1",
		Resources:  []string{},
		Detail:     json.RawMessage(cloudWatchPayload),
	}

	sqsBody, err := json.Marshal(cloudWatchEvent)
	assert.NoError(t, err)

	sqsEvent := &events.SQSEvent{
		Records: []events.SQSMessage{
			{
				MessageId: "1",
				Body:      string(sqsBody),
			},
		},
	}

	mockDynamo := newMockDynamodbClient(t)
	mockDynamo.EXPECT().
		OneByIdentity(ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).
		Return(nil)
	mockDynamo.EXPECT().
		PutUser(ctx, mock.Anything, "urn:fdc:gov.uk:2022:XXXX-XXXXXX").
		Return(nil)
	mockDynamo.EXPECT().
		Put(ctx, mock.Anything, mock.Anything).
		Return(nil)
	mockDynamo.EXPECT().
		ExistsLpaIDAndUserID(ctx, lpaUID, mock.MatchedBy(func(id string) bool {
			return len(id) > 0
		})).
		Return(false, nil)

	mockFactory := newMockFactory(t)
	mockFactory.EXPECT().
		DynamoClient().
		Return(mockDynamo)

	result, err := handler(ctx, mockFactory, sqsEvent)
	assert.Nil(t, err)
	assert.Empty(t, result["batchItemFailures"])
}

func TestInvalidJsonInSQSBody(t *testing.T) {
	logger = slog.New(slog.DiscardHandler)
	factory := &DefaultFactory{}

	sqsEvent := &events.SQSEvent{
		Records: []events.SQSMessage{
			{
				MessageId: "1",
				Body:      `invalid-json`,
			},
		},
	}

	result, err := handler(ctx, factory, sqsEvent)

	assert.NotNil(t, err)
	assert.Contains(t, err.Error(), "invalid character 'i'")
	assert.Len(t, result["batchItemFailures"], 1)
}

func TestUnsupportedCloudWatchEventType(t *testing.T) {
	logger = slog.New(slog.DiscardHandler)
	factory := &DefaultFactory{}

	cloudWatchPayload, err := json.Marshal(payload)
	assert.NoError(t, err)

	unsupportedDetailType := "unsupported-detail-type"
	cloudWatchEvent := &events.CloudWatchEvent{
		ID:         "1",
		DetailType: unsupportedDetailType,
		Source:     "opg.poas.makeregister",
		AccountID:  "123",
		Time:       time.Now(),
		Region:     "us-east-1",
		Resources:  []string{},
		Detail:     json.RawMessage(cloudWatchPayload),
	}

	sqsBody, err := json.Marshal(cloudWatchEvent)
	assert.NoError(t, err)

	sqsEvent := &events.SQSEvent{
		Records: []events.SQSMessage{
			{
				MessageId: "1",
				Body:      string(sqsBody),
			},
		},
	}

	result, err := handler(ctx, factory, sqsEvent)

	assert.NotNil(t, err)
	assert.Contains(t, err.Error(), "Unhandled event type: "+unsupportedDetailType)
	assert.Len(t, result["batchItemFailures"], 1)
}
