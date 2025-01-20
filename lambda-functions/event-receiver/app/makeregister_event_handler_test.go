package main

import (
    "context"
	"fmt"
	"testing"

	"github.com/aws/aws-lambda-go/events"
	"github.com/stretchr/testify/assert"
)

func TestMakeRegisterEventHandlerHandleUnknownEvent(t *testing.T) {
    ctx := context.Background()

	handler := &makeregisterEventHandler{}

	err := handler.Handle(ctx, nil, &events.CloudWatchEvent{DetailType: "some-event"})

	if err != nil {
	    t.Logf("Actual error: %v", err)
    }

	assert.Equal(t, fmt.Errorf("unknown lpastore event"), err)
}

func TestMakeRegisterEventHandlerLpaAccessGranted(t *testing.T) {
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

    cloudWatchEvent := &events.CloudWatchEvent{
        DetailType: "lpa-access-granted",
        Detail: []byte(payload),
    }

    err := handler.Handle(ctx, nil, cloudWatchEvent)

    assert.NoError(t, err)
}
