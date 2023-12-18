package handlers_test

import (
	"context"
	"testing"
)

type mockSystemMessageService struct{}

func (m mockSystemMessageService) GetSystemMessages(ctx context.Context) (systemMessages map[string]string, err error) {
	return nil, nil
}

func (m mockSystemMessageService) PutSystemMessages(ctx context.Context, messages map[string]string) (err error) {
	return nil
}

// 1. rendering template loads data from parameter store
// 2. save button calls appropriate functions (4 combinations) to save to parameter store

// template error panic test - do we need this?

func Test_RenderTemplateLoadsFromParameterStore(t *testing.T) {
	// mock class that loads
	t.Parallel()
	t.Errorf("Oh no !!!")
}

func Test_SaveButtonSavesToParameterStore(t *testing.T) {
	// mock class that loads
	t.Parallel()
	t.Errorf("Oh no 2!!!")
}
