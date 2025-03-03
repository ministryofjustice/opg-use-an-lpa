//go:generate mockery --all --recursive --output=./mocks --outpkg=mocks
package main

import (
	"context"
	"encoding/json"
	"errors"
	"github.com/google/uuid"
	"github.com/ministryofjustice/opg-go-common/telemetry"
	"github.com/ministryofjustice/opg-use-an-lpa/app/mocks"
	"github.com/stretchr/testify/mock"
	"testing"
	"time"

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

func (m *MockFactory) Now() func() time.Time {
	args := m.Called()
	return args.Get(0).(func() time.Time)
}

func (m *MockFactory) UuidString() func() string {
	args := m.Called()
	return args.Get(0).(func() string)
}

func (m *MockFactory) DynamoClient() DynamodbClient {
	return m.Called().Get(0).(DynamodbClient)
}

func TestMakeRegisterEventHandler_Success(t *testing.T) {
	ctx := context.Background()
	lpaId := "M-1234-5678-9012"

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

	mockDynamo := new(mocks.DynamodbClient)
	mockFactory := new(MockFactory)
	mockFactory.On("DynamoClient").Return(mockDynamo)

	existingUser := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
	}

	mockDynamo.On("OneByUID", ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).Run(func(args mock.Arguments) {
		user := args.Get(2).(*Actor)
		*user = existingUser
	}).Return(nil)
	mockDynamo.On("Put", ctx, mock.Anything).Return(nil)
	mockDynamo.On("GetByLpaIDAndUserID", ctx, lpaId, mock.MatchedBy(func(id string) bool {
		return len(id) > 0
	}), mock.Anything).Return(nil)

	err = handler.EventHandler(ctx, mockFactory, cloudWatchEvent)
	assert.NoError(t, err)

	mockDynamo.AssertCalled(t, "OneByUID", ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything)

	mockDynamo.AssertExpectations(t)
	mockFactory.AssertExpectations(t)

}

func TestHandleSQSEvent_UnmarshalFailure(t *testing.T) {
	ctx := context.Background()
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

	logger = telemetry.NewLogger("opg-use-an-lpa/event-receiver")

	mockDynamo := new(mocks.DynamodbClient)
	mockFactory := new(MockFactory)
	mockFactory.On("DynamoClient").Return(mockDynamo)

	mockDynamo.On("OneByUID", ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).Return(nil)
	mockDynamo.On("Put", ctx, mock.Anything).Return(nil)

	err = handler.EventHandler(ctx, mockFactory, cloudWatchEvent)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "json: cannot unmarshal string")
}

func TestHandleCloudWatchEvent_FailedToFindUser(t *testing.T) {
	ctx := context.Background()
	logger = telemetry.NewLogger("opg-use-an-lpa/event-receiver")
	userId := uuid.New().String()

	mockDynamo := new(mocks.DynamodbClient)
	mockFactory := new(MockFactory)

	simulatedError := errors.New("simulated error: Failed to find existing user")
	mockDynamo.On("OneByUID", ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).Return(simulatedError)

	mockFactory.On("DynamoClient").Return(mockDynamo)
	mockFactory.On("Now").Return(func() time.Time { return time.Now() }, nil)
	mockFactory.On("UuidString").Return(func() string { return "123" }, nil)

	mockDynamo.On("Put", ctx, mock.Anything).Return(nil)

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
	}

	err := handleUsers(ctx, mockDynamo, actor, userId)

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "Failed to find existing user")
}

func TestHandleCloudWatchEvent_FailedToPutActor(t *testing.T) {
	ctx := context.Background()
	userId := uuid.New().String()

	mockDynamo := new(mocks.DynamodbClient)

	simulatedError := errors.New("simulated error: Failed to put actor")
	mockDynamo.On("OneByUID", ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).Return(nil)
	mockDynamo.On("Put", ctx, mock.Anything).Return(simulatedError)

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
	}

	err := handleUsers(ctx, mockDynamo, actor, userId)

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "Failed to put actor")

	mockDynamo.AssertExpectations(t)
}

func TestHandleCloudWatchEvent_FailedToFindUserLpaMap(t *testing.T) {
	ctx := context.Background()
	logger = telemetry.NewLogger("opg-use-an-lpa/event-receiver")
	userId := uuid.New().String()
	lpaId := "M-1234-5678-9012"

	mockDynamo := new(mocks.DynamodbClient)
	mockFactory := new(MockFactory)

	simulatedError := errors.New("simulated error: Failed to find existing LPA")
	mockDynamo.On("GetByLpaIDAndUserID", ctx, lpaId, userId, mock.Anything).Return(simulatedError)

	mockFactory.On("DynamoClient").Return(mockDynamo)
	mockFactory.On("Now").Return(func() time.Time { return time.Now() }, nil)
	mockFactory.On("UuidString").Return(func() string { return "123" }, nil)

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
	}

	err := handleLpas(ctx, mockDynamo, actor, userId, lpaId)

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "Failed to find existing LPA")
}

func TestHandleCloudWatchEvent_FailedToPutUserLpaMap(t *testing.T) {
	ctx := context.Background()
	logger = telemetry.NewLogger("opg-use-an-lpa/event-receiver")
	userId := uuid.New().String()
	lpaId := "M-1234-5678-9012"

	mockDynamo := new(mocks.DynamodbClient)
	mockFactory := new(MockFactory)

	simulatedError := errors.New("simulated error: Failed to put Lpa")
	mockDynamo.On("GetByLpaIDAndUserID", ctx, lpaId, userId, mock.Anything).Return(nil)
	mockDynamo.On("Put", ctx, mock.Anything).Return(simulatedError)

	mockFactory.On("DynamoClient").Return(mockDynamo)
	mockFactory.On("Now").Return(func() time.Time { return time.Now() }, nil)
	mockFactory.On("UuidString").Return(func() string { return "123" }, nil)

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
	}

	err := handleLpas(ctx, mockDynamo, actor, userId, lpaId)

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "Failed to insert LPA mapping")
}

func TestHandleCloudWatchEvent_SuccessToFindUserLpaMap(t *testing.T) {
	ctx := context.Background()
	logger = telemetry.NewLogger("opg-use-an-lpa/event-receiver")
	userId := uuid.New().String()
	lpaId := "M-1234-5678-9012"

	mockDynamo := new(mocks.DynamodbClient)
	mockFactory := new(MockFactory)

	mockDynamo.On("GetByLpaIDAndUserID", ctx, lpaId, userId, mock.Anything).Return(nil)
	mockDynamo.On("Put", ctx, mock.Anything).Return(nil)

	mockFactory.On("DynamoClient").Return(mockDynamo)
	mockFactory.On("Now").Return(func() time.Time { return time.Now() }, nil)
	mockFactory.On("UuidString").Return(func() string { return "123" }, nil)

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
	}

	err := handleLpas(ctx, mockDynamo, actor, userId, lpaId)
	assert.NoError(t, err)
}

func TestHandleCloudWatchEvent_SuccessToPutUserLpaMap(t *testing.T) {
	ctx := context.Background()
	logger = telemetry.NewLogger("opg-use-an-lpa/event-receiver")
	userId := uuid.New().String()
	lpaId := "M-1234-5678-9012"

	mockDynamo := new(mocks.DynamodbClient)
	mockFactory := new(MockFactory)

	mockDynamo.On("GetByLpaIDAndUserID", ctx, lpaId, userId, mock.Anything).Return(nil)
	mockDynamo.On("Put", ctx, mock.Anything).Return(nil)

	mockFactory.On("DynamoClient").Return(mockDynamo)
	mockFactory.On("Now").Return(func() time.Time { return time.Now() }, nil)
	mockFactory.On("UuidString").Return(func() string { return "123" }, nil)

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
	}

	err := handleLpas(ctx, mockDynamo, actor, userId, lpaId)

	assert.NoError(t, err)
	assert.Nil(t, err)
}
