package main

import (
	"context"
	"encoding/json"
	"errors"
	"log/slog"
	"testing"
	"time"

	"github.com/aws/aws-lambda-go/events"
	"github.com/google/uuid"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
)

func TestMakeRegisterEventHandler_Success(t *testing.T) {
	ctx := context.Background()
	lpaUID := "M-1234-5678-9012"

	handler := &MakeRegisterEventHandler{}

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

	existingUser := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
	}

	mockDynamo := newMockDynamodbClient(t)
	mockDynamo.EXPECT().
		OneByIdentity(ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).
		Run(func(_ context.Context, _ string, v any) {
			user := v.(*Actor)
			*user = existingUser
		}).
		Return(nil)
	mockDynamo.EXPECT().
		PutUser(ctx, mock.MatchedBy(func(id string) bool {
			_, err := uuid.Parse(id)
			return err == nil
		}), "urn:fdc:gov.uk:2022:XXXX-XXXXXX").
		Return(nil)
	mockDynamo.EXPECT().
		Put(ctx, "UserLpaActorMap", mock.Anything).
		Return(nil)
	mockDynamo.EXPECT().
		ExistsLpaIDAndUserID(ctx, lpaUID, mock.MatchedBy(func(id string) bool {
			return len(id) > 0
		})).Return(false, nil)

	mockFactory := newMockFactory(t)
	mockFactory.EXPECT().
		DynamoClient().Return(mockDynamo)

	err = handler.EventHandler(ctx, mockFactory, cloudWatchEvent)
	assert.NoError(t, err)
}

func TestHandleSQSEvent_UnmarshalFailure(t *testing.T) {
	ctx := context.Background()
	logger = slog.New(slog.DiscardHandler)
	handler := &MakeRegisterEventHandler{}

	malformedBody := `{
		"uid":     "M-1234-5678-9012"
		"lpaType": "personal-welfare"
	}`

	cloudWatchPayload, err := json.Marshal(malformedBody)
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

	mockFactory := newMockFactory(t)
	mockFactory.EXPECT().
		DynamoClient().
		Return(newMockDynamodbClient(t))

	err = handler.EventHandler(ctx, mockFactory, cloudWatchEvent)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "json: cannot unmarshal string")
}

func TestHandleCloudWatchEvent_FailedToFindUser(t *testing.T) {
	ctx := context.Background()
	logger = slog.New(slog.DiscardHandler)

	mockDynamo := newMockDynamodbClient(t)
	mockDynamo.EXPECT().
		OneByIdentity(ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).
		Return(errors.New("simulated error: Failed to find existing user"))

	actor := &Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
	}

	err := handleUsers(ctx, mockDynamo, actor)

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "Failed to find existing user")
}

func TestHandleCloudWatchEvent_FailedToPutActor(t *testing.T) {
	ctx := context.Background()

	mockDynamo := newMockDynamodbClient(t)
	mockDynamo.EXPECT().
		OneByIdentity(ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).
		Return(nil)
	mockDynamo.EXPECT().
		PutUser(ctx, mock.Anything, mock.Anything).
		Return(errors.New("simulated error: Failed to put actor"))

	actor := &Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
	}

	err := handleUsers(ctx, mockDynamo, actor)

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "Failed to put actor")
}

func TestHandleCloudWatchEvent_FailedToFindUserLpaMap(t *testing.T) {
	ctx := context.Background()
	logger = slog.New(slog.DiscardHandler)
	userId := uuid.New().String()
	lpaUID := "M-1234-5678-9012"

	mockDynamo := newMockDynamodbClient(t)
	mockDynamo.EXPECT().
		ExistsLpaIDAndUserID(ctx, lpaUID, userId).
		Return(false, errors.New("simulated error: Failed to find existing LPA"))

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
		Id:        userId,
	}

	err := handleLpas(ctx, mockDynamo, actor, lpaUID)

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "Failed to find existing LPA")
}

func TestHandleCloudWatchEvent_FailedToPutUserLpaMap(t *testing.T) {
	ctx := context.Background()
	logger = slog.New(slog.DiscardHandler)
	userId := uuid.New().String()
	lpaUID := "M-1234-5678-9012"

	mockDynamo := newMockDynamodbClient(t)
	mockDynamo.EXPECT().
		ExistsLpaIDAndUserID(ctx, lpaUID, userId).
		Return(false, nil)
	mockDynamo.EXPECT().
		Put(ctx, mock.Anything, mock.Anything).
		Return(errors.New("simulated error: Failed to put Lpa"))

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
		Id:        userId,
	}

	err := handleLpas(ctx, mockDynamo, actor, lpaUID)

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "Failed to insert LPA mapping")
}

func TestHandleCloudWatchEvent_SuccessToFindUserLpaMap(t *testing.T) {
	ctx := context.Background()
	logger = slog.New(slog.DiscardHandler)
	userId := uuid.New().String()
	lpaUID := "M-1234-5678-9012"

	mockDynamo := newMockDynamodbClient(t)
	mockDynamo.EXPECT().
		ExistsLpaIDAndUserID(ctx, lpaUID, userId).
		Return(true, nil)

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
		Id:        userId,
	}

	err := handleLpas(ctx, mockDynamo, actor, lpaUID)
	assert.NoError(t, err)
}

func TestHandleCloudWatchEvent_SuccessToPutUserLpaMap(t *testing.T) {
	ctx := context.Background()
	logger = slog.New(slog.DiscardHandler)
	userId := uuid.New().String()
	lpaUID := "M-1234-5678-9012"

	mockDynamo := newMockDynamodbClient(t)
	mockDynamo.EXPECT().
		ExistsLpaIDAndUserID(ctx, lpaUID, userId).
		Return(false, nil)
	mockDynamo.EXPECT().
		Put(ctx, mock.Anything, mock.Anything).
		Return(nil)

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
		Id:        userId,
	}

	err := handleLpas(ctx, mockDynamo, actor, lpaUID)

	assert.NoError(t, err)
	assert.Nil(t, err)
}
