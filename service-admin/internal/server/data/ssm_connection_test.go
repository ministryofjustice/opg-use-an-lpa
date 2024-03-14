package data_test

import (
	"context"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/stretchr/testify/assert"
	"testing"
)

type MockSSMClient struct{}

func (m MockSSMClient) PutParameter(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error) {
	return nil, nil
}

func (m MockSSMClient) GetParameter(ctx context.Context, params *ssm.GetParameterInput, optFns ...func(*ssm.Options)) (*ssm.GetParameterOutput, error) {
	return nil, nil
}

func (m MockSSMClient) DeleteParameter(ctx context.Context, params *ssm.DeleteParameterInput, optFns ...func(*ssm.Options)) (*ssm.DeleteParameterOutput, error) {
	return nil, nil
}

// test for new method, which takes in ssmclient
func TestNewSSMConnection(t *testing.T) {
	t.Parallel()

	got := data.NewSSMConnection(&MockSSMClient{}, "")
	assert.NotNil(t, got)
}
