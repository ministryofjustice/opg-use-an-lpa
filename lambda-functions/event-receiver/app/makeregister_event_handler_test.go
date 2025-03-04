//go:generate mockery --all --recursive --output=./mocks --outpkg=mocks
package main

import (
	"context"
	"encoding/json"
	"errors"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
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

func (m *MockDynamoDbClient) OneByIdentity(ctx context.Context, subjectID string, v interface{}) error {
	args := m.Called(ctx, subjectID, v)
	return args.Error(0)
}

func (m *MockDynamoDbClient) Put(ctx context.Context, v map[string]types.AttributeValue) error {
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

	mockDynamo.On("OneByIdentity", ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).Run(func(args mock.Arguments) {
		user := args.Get(2).(*Actor)
		*user = existingUser
	}).Return(nil)

	mockDynamo.On("Put", ctx, mock.MatchedBy(func(tableName string) bool {
		return tableName == "ActorUsers"
	}), mock.MatchedBy(func(input map[string]types.AttributeValue) bool {
		idAttr, ok := input["Id"].(*types.AttributeValueMemberS)

		_, err := uuid.Parse(idAttr.Value)
		if err != nil {
			return false
		}
		identityAttr, ok := input["Identity"].(*types.AttributeValueMemberS)
		return ok && identityAttr.Value == "urn:fdc:gov.uk:2022:XXXX-XXXXXX"
	})).Return(nil)

	mockDynamo.On("Put", ctx, mock.MatchedBy(func(tableName string) bool {
		return tableName == "UserLpaActorMap"
	}), mock.Anything).Return(nil)

	mockDynamo.On("ExistsLpaIDAndUserID", ctx, lpaId, mock.MatchedBy(func(id string) bool {
		return len(id) > 0
	})).Return(false, nil)

	err = handler.EventHandler(ctx, mockFactory, cloudWatchEvent)
	assert.NoError(t, err)

	mockDynamo.AssertCalled(t, "OneByIdentity", ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything)
	mockDynamo.AssertCalled(t, "Put", ctx, "ActorUsers", mock.Anything)
	mockDynamo.AssertCalled(t, "Put", ctx, "UserLpaActorMap", mock.Anything)
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

	mockDynamo.On("OneByIdentity", ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).Return(nil)
	mockDynamo.On("Put", ctx, mock.Anything, mock.Anything).Return(nil)

	err = handler.EventHandler(ctx, mockFactory, cloudWatchEvent)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "json: cannot unmarshal string")
}

func TestHandleCloudWatchEvent_FailedToFindUser(t *testing.T) {
	ctx := context.Background()
	logger = telemetry.NewLogger("opg-use-an-lpa/event-receiver")

	mockDynamo := new(mocks.DynamodbClient)
	mockFactory := new(MockFactory)

	simulatedError := errors.New("simulated error: Failed to find existing user")
	mockDynamo.On("OneByIdentity", ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).Return(simulatedError)

	mockFactory.On("DynamoClient").Return(mockDynamo)
	mockFactory.On("Now").Return(func() time.Time { return time.Now() }, nil)
	mockFactory.On("UuidString").Return(func() string { return "123" }, nil)

	mockDynamo.On("Put", ctx, actorUserTable, mock.Anything).Return(nil)

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

	mockDynamo := new(mocks.DynamodbClient)

	simulatedError := errors.New("simulated error: Failed to put actor")
	mockDynamo.On("OneByIdentity", ctx, "urn:fdc:gov.uk:2022:XXXX-XXXXXX", mock.Anything).Return(nil)
	mockDynamo.On("Put", ctx, mock.Anything, mock.Anything).Return(simulatedError)

	actor := &Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
	}

	err := handleUsers(ctx, mockDynamo, actor)

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
	mockDynamo.On("ExistsLpaIDAndUserID", ctx, lpaId, userId).Return(false, simulatedError)

	mockFactory.On("DynamoClient").Return(mockDynamo)
	mockFactory.On("Now").Return(func() time.Time { return time.Now() }, nil)
	mockFactory.On("UuidString").Return(func() string { return "123" }, nil)

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
		Id:        userId,
	}

	err := handleLpas(ctx, mockDynamo, actor, lpaId)

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
	mockDynamo.On("ExistsLpaIDAndUserID", ctx, lpaId, userId).Return(false, nil)
	mockDynamo.On("Put", ctx, mock.Anything, mock.Anything).Return(simulatedError)

	mockFactory.On("DynamoClient").Return(mockDynamo)
	mockFactory.On("Now").Return(func() time.Time { return time.Now() }, nil)
	mockFactory.On("UuidString").Return(func() string { return "123" }, nil)

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
		Id:        userId,
	}

	err := handleLpas(ctx, mockDynamo, actor, lpaId)

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

	mockDynamo.On("ExistsLpaIDAndUserID", ctx, lpaId, userId).Return(true, nil)
	mockDynamo.On("Put", ctx, mock.Anything, mock.Anything).Return(nil)

	mockFactory.On("DynamoClient").Return(mockDynamo)
	mockFactory.On("Now").Return(func() time.Time { return time.Now() }, nil)
	mockFactory.On("UuidString").Return(func() string { return "123" }, nil)

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
		Id:        userId,
	}

	err := handleLpas(ctx, mockDynamo, actor, lpaId)
	assert.NoError(t, err)
}

func TestHandleCloudWatchEvent_SuccessToPutUserLpaMap(t *testing.T) {
	ctx := context.Background()
	logger = telemetry.NewLogger("opg-use-an-lpa/event-receiver")
	userId := uuid.New().String()
	lpaId := "M-1234-5678-9012"

	mockDynamo := new(mocks.DynamodbClient)
	mockFactory := new(MockFactory)

	mockDynamo.On("ExistsLpaIDAndUserID", ctx, lpaId, userId).Return(false, nil)
	mockDynamo.On("Put", ctx, mock.Anything, mock.Anything).Return(nil)

	mockFactory.On("DynamoClient").Return(mockDynamo)
	mockFactory.On("Now").Return(func() time.Time { return time.Now() }, nil)
	mockFactory.On("UuidString").Return(func() string { return "123" }, nil)

	actor := Actor{
		ActorUID:  "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d",
		SubjectID: "urn:fdc:gov.uk:2022:XXXX-XXXXXX",
		Id:        userId,
	}

	err := handleLpas(ctx, mockDynamo, actor, lpaId)

	assert.NoError(t, err)
	assert.Nil(t, err)
}
