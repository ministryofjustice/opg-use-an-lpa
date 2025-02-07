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
