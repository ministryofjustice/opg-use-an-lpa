//go:generate mockery --all --recursive --output=./mocks --outpkg=mocks
package main

import (
	"context"
	"encoding/json"
	"github.com/ministryofjustice/opg-go-common/telemetry"
	"testing"
	"time"

	"github.com/aws/aws-lambda-go/events"
	"github.com/stretchr/testify/assert"
)

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

	missingDetailType := "incorrect-value"

	cloudWatchMessage := &events.CloudWatchEvent{
		ID:         "1",
		DetailType: missingDetailType,
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
	assert.Equal(t, "Unhandled event type: "+missingDetailType, err.Error())
	assert.Len(t, result["batchItemFailures"], 1)
}
