package data_test

import (
	"context"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/stretchr/testify/assert"
	"testing"
)

type mockSSMConnection struct {
}

func TestSystemMessages(t *testing.T) {
	t.Parallel()
	config, _ := config.LoadDefaultConfig(context.TODO())
	ssmConn := data.NewSSMConnection(ssm.NewFromConfig(config))
	service := data.NewSystemMessageService(*ssmConn)

	initialMessages := map[string]string{"system-message-use-en": "use hello world en", "system-message-use-cy": "use helo byd",
		"system-message-view-en": "view hello world", "system-message-view-cy": "view helo byd"}
	err := service.PutSystemMessages(context.Background(), initialMessages)
	if err != nil {
		t.Errorf("%w", err)
	}

	messages, _ := service.GetSystemMessages(context.Background())
	assert.NotEmpty(t, messages)
}
