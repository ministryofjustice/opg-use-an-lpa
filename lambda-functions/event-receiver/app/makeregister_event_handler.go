package main

import (
	"context"
	"encoding/json"
	"fmt"

	"github.com/aws/aws-lambda-go/events"
)

type makeregisterEventHandler struct{}

type Actor struct {
	ActorUID    string `json:"actorUid"`
	SubjectID   string `json:"subjectId"`
}

type lpaAccessGranted struct {
	UID        string   `json:"uid"`
	LpaType    string   `json:"lpaType"`
	Actors     []Actor  `json:"actors"`
}

func (h *makeregisterEventHandler) Handle(ctx context.Context, factory factory, cloudWatchEvent *events.CloudWatchEvent) error {

    if cloudWatchEvent.DetailType == "lpa-access-granted" {
		var data lpaAccessGranted
		if err := json.Unmarshal(cloudWatchEvent.Detail, &data); err != nil {
			return fmt.Errorf("failed to unmarshal detail: %w", err)
		}
        fmt.Printf("Successfully unmarshalled LPA Access Granted: %+v\n", data.UID)
	} else {
	    return fmt.Errorf("unknown lpastore event")
	}

    return nil
}
