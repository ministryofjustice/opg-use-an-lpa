package handlers_test

import (
	"context"
	"testing"
)

type mockSystemMessageService struct{}

func (m mockSystemMessageService) GetSystemMessages(ctx context.Context) (systemMessages map[string]string, err error) {
	return map[string]string{"system-message-use-en": "use hello world en", "system-message-use-cy": "use helo byd",
		"system-message-view-en": "view hello world", "system-message-view-cy": "view helo byd"}, nil
}

func (m mockSystemMessageService) PutSystemMessages(ctx context.Context, messages map[string]string) (err error) {
	return nil
}

// TODO template error panic test - do we need this?

func Test_RenderTemplateLoadsFromParameterStore(t *testing.T) {
	t.Parallel()
	// TODO the mock should record a call to GetSystemMessages
	// TODO the text areas should now contain the messages defined in the mock at the top of this file
	t.Errorf("Oh no !!!")
}

func Test_SaveButtonSavesToParameterStore(t *testing.T) {
	t.Parallel()
	// TODO ensure we called PutSsytemMessages on mock service

	t.Errorf("Oh no 2!!!")
}
