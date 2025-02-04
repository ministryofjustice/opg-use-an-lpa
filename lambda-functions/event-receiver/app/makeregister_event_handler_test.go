//go:generate mockery --all --recursive --output=./mocks --outpkg=mocks
package main

import (
	"context"
	"testing"

	"github.com/aws/aws-lambda-go/events"
	"github.com/stretchr/testify/assert"
)

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

	sqsMessage := &events.SQSMessage{
		MessageId: "1",
		Body:      payload,
	}

	err := handler.Handle(ctx, sqsMessage)

	assert.NoError(t, err)
}
