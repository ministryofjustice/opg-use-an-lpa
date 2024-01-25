package data_test

import (
	"context"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
	"github.com/aws/aws-sdk-go-v2/service/ssm/types"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/stretchr/testify/assert"
	"testing"
)

type mockSSMClient struct {
	PutParameterFunc         func(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error)
	PutParameterCallCount    int
	GetParameterFunc         func(ctx context.Context, params *ssm.GetParameterInput, optFns ...func(*ssm.Options)) (*ssm.GetParameterOutput, error)
	DeleteParameterFunc      func(ctx context.Context, params *ssm.DeleteParameterInput, optFns ...func(*ssm.Options)) (*ssm.DeleteParameterOutput, error)
	DeleteParameterCallCount int
	parameters               map[string]string
}

func (m mockSSMClient) PutParameter(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error) {
	if m.PutParameterFunc != nil {
		return m.PutParameterFunc(ctx, params, optFns...)
	}

	return nil, nil
}

func (m mockSSMClient) GetParameter(ctx context.Context, params *ssm.GetParameterInput, optFns ...func(*ssm.Options)) (*ssm.GetParameterOutput, error) {
	if m.GetParameterFunc != nil {
		return m.GetParameterFunc(ctx, params, optFns...)
	}
	return nil, nil // TODO should this return a default value?
}

func (m mockSSMClient) DeleteParameter(ctx context.Context, params *ssm.DeleteParameterInput, optFns ...func(*ssm.Options)) (*ssm.DeleteParameterOutput, error) {
	if m.DeleteParameterFunc != nil {
		return m.DeleteParameterFunc(ctx, params, optFns...)
	}

	return &ssm.DeleteParameterOutput{}, nil
}

func TestPutSystemMessages(t *testing.T) {
	t.Parallel()

	// Initial set of messages
	initialMessages := map[string]string{
		"system-message-use-en":  "use hello world en",
		"system-message-use-cy":  "use helo byd",
		"system-message-view-en": "view hello world",
		"system-message-view-cy": "view helo byd",
	}

	var mockClient *mockSSMClient
	mockClient = &mockSSMClient{
		PutParameterFunc: func(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error) {
			mockClient.PutParameterCallCount++

			if value, containsKey := initialMessages[*params.Name]; containsKey {
				if value != *params.Value && *params.Value != "" { // Check for non-empty values
					t.Errorf("Unexpected message value given for %s", *params.Name)
				}
			} else {
				t.Errorf("Unexpected message key used: %s", *params.Name)
			}

			if params.Overwrite == nil && *params.Value != "" { // Check overwrite only for non-empty values
				t.Errorf("Expecting Overwrite option to be set for %s", *params.Name)
			}

			return nil, nil
		},

		DeleteParameterFunc: func(ctx context.Context, params *ssm.DeleteParameterInput, optFns ...func(*ssm.Options)) (*ssm.DeleteParameterOutput, error) {
			mockClient.DeleteParameterCallCount++

			return nil, nil
		},
	}

	ssmConn := data.NewSSMConnection(mockClient)
	service := data.NewSystemMessageService(*ssmConn)

	// Test adding messages
	_, err := service.PutSystemMessages(context.Background(), initialMessages)
	if err != nil {
		t.Errorf("Failure during write of parameter %s", err)
	}
	assert.Equal(t, 4, mockClient.PutParameterCallCount, "Expected PutParameter to be called 4 times for adding")

	// Reset the call count for deletion
	mockClient.PutParameterCallCount = 0
	mockClient.DeleteParameterCallCount = 0

	// Simulate deleting a message by setting its value to an empty string
	messagesToDelete := map[string]string{
		"system-message-use-en": "",
	}

	// Test deleting a message
	deleted, err := service.PutSystemMessages(context.Background(), messagesToDelete)
	assert.NoError(t, err)
	assert.True(t, deleted, "Expected at least one message to be deleted")
	assert.Equal(t, 0, mockClient.PutParameterCallCount, "Expected PutParameter to be called once for deleting")
	assert.Equal(t, 1, mockClient.DeleteParameterCallCount, "Expected DeleteParameter to be called once for deleting")
}

func TestGetSystemMessages(t *testing.T) {
	t.Parallel()

	predefinedValues := map[string]string{
		"system-message-use-en":  "use hello world en",
		"system-message-use-cy":  "use helo byd",
		"system-message-view-en": "view hello world",
		"system-message-view-cy": "view helo byd",
	}

	mockClient := &mockSSMClient{
		GetParameterFunc: func(ctx context.Context, params *ssm.GetParameterInput, optFns ...func(*ssm.Options)) (*ssm.GetParameterOutput, error) {
			if value, ok := predefinedValues[*params.Name]; ok {
				return &ssm.GetParameterOutput{
					Parameter: &types.Parameter{Value: aws.String(value)},
				}, nil
			}
			return nil, &types.ParameterNotFound{}
		},
	}

	ssmConn := data.NewSSMConnection(mockClient)
	service := data.NewSystemMessageService(*ssmConn)

	messages, err := service.GetSystemMessages(context.Background())

	assert.NoError(t, err)
	assert.Equal(t, predefinedValues, messages)
}
