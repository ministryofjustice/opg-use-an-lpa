package data_test

import (
	"context"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/stretchr/testify/assert"
	"testing"
)

type mockSSMClient struct {
	PutParameterFunc      func(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error)
	PutParameterCallCount int
}

func (m mockSSMClient) PutParameter(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error) {
	if m.PutParameterFunc != nil {
		return m.PutParameterFunc(ctx, params, optFns...)
	}

	return nil, nil
}

func (m mockSSMClient) GetParameter(ctx context.Context, params *ssm.GetParameterInput, optFns ...func(*ssm.Options)) (*ssm.GetParameterOutput, error) {
	return nil, nil // TODO should this be some actual data rather than nil?  or are we just happy we called the ssm client?
}

func TestPutSystemMessages(t *testing.T) {
	t.Parallel()

	initialMessages := map[string]string{"system-message-use-en": "use hello world en", "system-message-use-cy": "use helo byd",
		"system-message-view-en": "view hello world", "system-message-view-cy": "view helo byd"}

	var mockClient *mockSSMClient
	mockClient = &mockSSMClient{
		PutParameterFunc: func(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error) {
			mockClient.PutParameterCallCount++

			if value, containsKey := initialMessages[*params.Name]; containsKey {
				if value != *params.Value {
					t.Errorf("Unexpected message value given")
				}
			} else {
				t.Errorf("Unexpected message key used")
			}

			if params.Overwrite == nil {
				t.Errorf("expecting Overwrite option to be set")
				t.FailNow()
			}

			return nil, nil
		},
	}

	ssmConn := data.NewSSMConnection(mockClient)
	service := data.NewSystemMessageService(*ssmConn)

	err := service.PutSystemMessages(context.Background(), initialMessages)

	if err != nil {
		t.Errorf("Failure during write of parameter %s", err)
	}
	if mockClient.PutParameterCallCount != 4 {
		t.Errorf("Expected PutParameter to be called 4 times")
	}
}

func TestGetSystemMessages(t *testing.T) {
	t.Parallel()

	ssmConn := data.NewSSMConnection(mockSSMClient{})
	service := data.NewSystemMessageService(*ssmConn)
	messages, _ := service.GetSystemMessages(context.Background())
	// TODO mock needs to actually return something now?  or do we just check it got called?
	assert.NotEmpty(t, messages)
}
