package data_test

import (
	"context"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/stretchr/testify/assert"
	"testing"
)

type mockSSMClient struct {
}

func (m mockSSMClient) PutParameter(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error) {
	return nil, nil
}

func (m mockSSMClient) GetParameter(ctx context.Context, params *ssm.GetParameterInput, optFns ...func(*ssm.Options)) (*ssm.GetParameterOutput, error) {
	return nil, nil
}

func TestPutSystemMessages(t *testing.T) {
	t.Parallel()
	ssmConn := data.NewSSMConnection(mockSSMClient{})
	service := data.NewSystemMessageService(*ssmConn)

	initialMessages := map[string]string{"system-message-use-en": "use hello world en", "system-message-use-cy": "use helo byd",
		"system-message-view-en": "view hello world", "system-message-view-cy": "view helo byd"}
	err := service.PutSystemMessages(context.Background(), initialMessages)
	if err != nil {
		t.Errorf("Failure during write of parameter %s", err)
	}

}

func TestGetSystemMessages(t *testing.T) {
	t.Parallel()
	ssmConn := data.NewSSMConnection(mockSSMClient{})
	service := data.NewSystemMessageService(*ssmConn)
	messages, _ := service.GetSystemMessages(context.Background())
	assert.NotEmpty(t, messages)
}
