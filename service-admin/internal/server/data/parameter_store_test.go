package data_test

import (
	"context"
	"errors"
	"testing"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
	"github.com/aws/aws-sdk-go-v2/service/ssm/types"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/stretchr/testify/assert"
)

type MockSSMClient struct{}

func (m MockSSMClient) PutParameter(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error) {
	if *params.Name == "error" {
		return nil, errors.New("mock error")
	}
	return &ssm.PutParameterOutput{}, nil
}

func (m MockSSMClient) GetParameter(ctx context.Context, params *ssm.GetParameterInput, optFns ...func(*ssm.Options)) (*ssm.GetParameterOutput, error) {
	if *params.Name == "error" {
		return nil, errors.New("mock error")
	}
	return &ssm.GetParameterOutput{
		Parameter: &types.Parameter{
			Value: aws.String("mockValue"),
		},
	}, nil
}

func TestReadParameterError(t *testing.T) {
	t.Parallel()

	conn := data.NewSSMConnection(MockSSMClient{})
	_, err := conn.ReadParameter("error")

	assert.Error(t, err)
	assert.Equal(t, "error reading parameter: mock error", err.Error())
}

func TestWriteParameterError(t *testing.T) {
	t.Parallel()

	conn := data.NewSSMConnection(MockSSMClient{})
	err := conn.WriteParameter("error", "mockValue")

	assert.Error(t, err)
	assert.Equal(t, "error writing parameter: mock error", err.Error())
}

func TestReadParameter(t *testing.T) {
	t.Parallel()

	conn := data.NewSSMConnection(MockSSMClient{})
	value, err := conn.ReadParameter("mockKey")

	assert.NoError(t, err)
	assert.Equal(t, "mockValue", value)
}

func TestWriteParameter(t *testing.T) {
	t.Parallel()

	conn := data.NewSSMConnection(MockSSMClient{})
	err := conn.WriteParameter("mockKey", "mockValue")

	assert.NoError(t, err)
}
