package main

import (
	"context"
	"encoding/json"
	"fmt"
	"github.com/aws/aws-lambda-go/events"
)

type makeregisterEventHandler struct{}

type Actor struct {
	ActorUID  string `json:"actorUid"`
	SubjectID string `json:"subjectId"`
}

type lpaAccessGranted struct {
	UID     string  `json:"uid"`
	LpaType string  `json:"lpaType"`
	Actors  []Actor `json:"actors"`
}

func (h *makeregisterEventHandler) Handle(ctx context.Context, record *events.SQSMessage) error {

	var data lpaAccessGranted

	if err := json.Unmarshal([]byte(record.Body), &data); err != nil {
		return fmt.Errorf("failed to unmarshal SQS message: %w", err)
	}

	fmt.Printf("Successfully unmarshalled LPA Access Granted: %+v\n", data.UID)

	return nil
}
