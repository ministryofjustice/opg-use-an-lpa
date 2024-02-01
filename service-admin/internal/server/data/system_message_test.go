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

func (m *mockSSMClient) DeleteParameter(ctx context.Context, params *ssm.DeleteParameterInput, optFns ...func(*ssm.Options)) (*ssm.DeleteParameterOutput, error) {
	m.DeleteParameterCallCount++
	if m.DeleteParameterFunc != nil {
		return m.DeleteParameterFunc(ctx, params, optFns...)
	}

	return &ssm.DeleteParameterOutput{}, nil
}

func TestPutSystemMessages(t *testing.T) {
	t.Parallel()

	initialMessages := map[string]string{
		"system-message-use-en":  "use hello world en",
		"system-message-use-cy":  "use helo byd",
		"system-message-view-en": "view hello world",
		"system-message-view-cy": "view helo byd",
	}

	mockClient := &mockSSMClient{
		PutParameterFunc: func(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error) {
			if value, exists := initialMessages[*params.Name]; exists {
				if value != *params.Value && *params.Value != "" {
					t.Errorf("Unexpected message value given for %s", *params.Name)
				}
			} else {
				t.Errorf("Unexpected message key used: %s", *params.Name)
			}
			if params.Overwrite == nil && *params.Value != "" {
				t.Errorf("Expecting Overwrite option to be set for %s", *params.Name)
			}
			return nil, nil
		},
		DeleteParameterFunc: func(ctx context.Context, params *ssm.DeleteParameterInput, optFns ...func(*ssm.Options)) (*ssm.DeleteParameterOutput, error) {
			return nil, nil
		},
	}

	ssmConn := data.NewSSMConnection(mockClient)
	service := data.NewSystemMessageService(*ssmConn)

	_, err := service.PutSystemMessages(context.Background(), initialMessages)
	assert.NoError(t, err)
	assert.Equal(t, 4, mockClient.PutParameterCallCount, "Expected PutParameter to be called 4 times for adding")

	mockClient.PutParameterCallCount = 0
	mockClient.DeleteParameterCallCount = 0

	messagesToDelete := map[string]string{
		"system-message-use-en": "",
	}

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
