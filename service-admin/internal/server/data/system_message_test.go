package data_test

import (
	"context"
	"fmt"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
	"github.com/aws/aws-sdk-go-v2/service/ssm/types"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/stretchr/testify/assert"
	"strings"
	"testing"
)

type mockSSMClient struct {
	PutParameterFunc      func(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error)
	PutParameterCallCount int
	GetParameterFunc      func(ctx context.Context, params *ssm.GetParameterInput, optFns ...func(*ssm.Options)) (*ssm.GetParameterOutput, error)
}

func (m *mockSSMClient) PutParameter(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error) {
	m.PutParameterCallCount++
	if m.PutParameterFunc != nil {
		return m.PutParameterFunc(ctx, params, optFns...)
	}

	return nil, nil
}

func (m *mockSSMClient) GetParameter(ctx context.Context, params *ssm.GetParameterInput, optFns ...func(*ssm.Options)) (*ssm.GetParameterOutput, error) {
	if m.GetParameterFunc != nil {
		return m.GetParameterFunc(ctx, params, optFns...)
	}

	return nil, nil
}

func TestPutSystemMessages(t *testing.T) {
	t.Parallel()

	// Test for adding messages
	initialMessages := map[string]string{
		"/system-message/use/en":  "use hello world en",
		"/system-message/use/cy":  "use helo byd",
		"/system-message/view/en": "view hello world",
		"/system-message/view/cy": "view helo byd",
	}

	mockClient := &mockSSMClient{
		PutParameterFunc: func(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error) {
			expectedValue, exists := initialMessages[*params.Name]
			if !exists {
				t.Errorf("Unexpected message key used: %s", *params.Name)
				return nil, nil
			}
			if strings.TrimSpace(*params.Value) != strings.TrimSpace(expectedValue) {
				t.Errorf("Unexpected message value given for %s: got '%s', want '%s'", *params.Name, *params.Value, expectedValue)
			}
			if params.Overwrite == nil || !*params.Overwrite {
				t.Errorf("Expecting Overwrite option to be set for %s", *params.Name)
			}
			return nil, nil
		},
	}

	ssmConn := data.NewSSMConnection(mockClient, "")
	service := data.NewSystemMessageService(*ssmConn)

	updated, deleted, err := service.PutSystemMessages(context.Background(), initialMessages)
	assert.NoError(t, err)
	assert.True(t, updated, "Expected at least one message to be marked as updated")
	assert.False(t, deleted, "Expected no messages to be marked as deleted")
	assert.Equal(t, 4, mockClient.PutParameterCallCount, "Expected PutParameter to be called 4 times for adding")

	mockClient.PutParameterCallCount = 0

	// Test for deleting a message
	messagesToDelete := map[string]string{
		"/system-message/use/en": "",
	}

	mockClient.PutParameterFunc = func(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error) {
		if *params.Value == " " {
			return nil, nil
		}

		t.Errorf("Unexpected message value given for %s: got '%s'", *params.Name, *params.Value)

		return nil, nil
	}

	updated, deleted, err = service.PutSystemMessages(context.Background(), messagesToDelete)
	assert.NoError(t, err)
	assert.False(t, updated, "Expected no messages to be marked as updated")
	assert.True(t, deleted, "Expected at least one message to be marked as deleted")
	assert.Equal(t, 1, mockClient.PutParameterCallCount, "Expected PutParameter to be called once for setting empty value")
}

func TestPutSystemMessages_ErrorHandling_ErrorWritingParameter(t *testing.T) {
	t.Parallel()

	messages := map[string]string{
		"/system-message/use/en": "use hello world en",
		"/system-message/use/cy": "use helo byd",
	}

	mockClient := &mockSSMClient{
		PutParameterFunc: func(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error) {
			return nil, fmt.Errorf("Failed to write parameter")
		},
	}

	ssmConn := data.NewSSMConnection(mockClient, "")
	service := data.NewSystemMessageService(*ssmConn)

	updated, deleted, err := service.PutSystemMessages(context.Background(), messages)
	assert.Error(t, err, "Should have reported error")
	assert.False(t, updated, "Expected no messages to be marked as updated")
	assert.False(t, deleted, "Expected no messages to be marked as deleted")
}

func TestGetSystemMessages(t *testing.T) {
	t.Parallel()

	predefinedValues := map[string]string{
		"/system-message/use/en":  "use hello world en",
		"/system-message/use/cy":  "use helo byd",
		"/system-message/view/en": "view hello world",
		"/system-message/view/cy": "view helo byd",
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

	ssmConn := data.NewSSMConnection(mockClient, "")
	service := data.NewSystemMessageService(*ssmConn)

	messages, err := service.GetSystemMessages(context.Background())
	assert.NoError(t, err)
	assert.Equal(t, predefinedValues, messages)
}

func TestGetSystemMessages_ErrorHandling_FailedToRetrieve(t *testing.T) {
	t.Parallel()

	predefinedValues := map[string]string{
		"/system-message/use/en": "use hello world en",
		"/system-message/use/cy": "use helo byd",
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

	ssmConn := data.NewSSMConnection(mockClient, "")
	service := data.NewSystemMessageService(*ssmConn)

	messages, err := service.GetSystemMessages(context.Background())
	assert.NoError(t, err)

	// Should return present messages and ignore missing parameters /system-message/view/en and /system-message/view/cy
	assert.Equal(t, predefinedValues, messages)
}
