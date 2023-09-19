package data_test

import (
	"context"
	"testing"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
	"github.com/aws/aws-sdk-go-v2/service/ssm/types"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/stretchr/testify/assert"
)

type MockSSMClient struct{}

func (m MockSSMClient) PutParameter(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error) {
	return &ssm.PutParameterOutput{}, nil
}

func (m MockSSMClient) GetParameter(ctx context.Context, params *ssm.GetParameterInput, optFns ...func(*ssm.Options)) (*ssm.GetParameterOutput, error) {
	return &ssm.GetParameterOutput{
		Parameter: &types.Parameter{
			Value: aws.String("mockValue"),
		},
	}, nil
}

func TestReadParameter(t *testing.T) {
	t.Parallel()

	conn := data.SSMConnection{Client: MockSSMClient{}}
	value, err := conn.ReadParameter("mockKey")

	assert.NoError(t, err)
	assert.Equal(t, "mockValue", value)
}

func TestWriteParameter(t *testing.T) {
	t.Parallel()

	conn := data.SSMConnection{Client: MockSSMClient{}}
	err := conn.WriteParameter("mockKey", "mockValue")

	assert.NoError(t, err)
}
