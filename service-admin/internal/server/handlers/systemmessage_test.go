package handlers_test

import (
	"context"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/stretchr/testify/assert"
	"net/http"
	"net/http/httptest"
	"net/url"
	"strings"
	"testing"
)

type mockSystemMessageService struct {
	getMessagesFunc func(ctx context.Context) (map[string]string, error)
	putMessagesFunc func(ctx context.Context, messages map[string]string) (bool, error)
}

func (m *mockSystemMessageService) GetSystemMessages(ctx context.Context) (map[string]string, error) {
	return m.getMessagesFunc(ctx)
}

func (m *mockSystemMessageService) PutSystemMessages(ctx context.Context, messages map[string]string) (bool, error) {
	return m.putMessagesFunc(ctx, messages)
}

func Test_RenderTemplateLoadsFromParameterStore(t *testing.T) {
	t.Parallel()

	mockSysMsgService := &mockSystemMessageService{
		getMessagesFunc: func(ctx context.Context) (map[string]string, error) {
			return map[string]string{
				"use-eng":  "use hello world en",
				"use-cy":   "use helo byd",
				"view-eng": "view hello world",
				"view-cy":  "view helo byd",
			}, nil
		},
	}

	getMessagesCalled := false

	mockTemplateService := &mockTemplateWriterService{
		RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, templateName string, data interface{}) error {
			assert.Equal(t, "systemmessage.page.gohtml", templateName)
			assert.IsType(t, handlers.SystemMessageData{}, data)
			getMessagesCalled = true
			return nil
		},
	}

	server := handlers.NewSystemMessageServer(mockSysMsgService, mockTemplateService)

	req, err := http.NewRequest("GET", "/some-url", nil)
	assert.NoError(t, err)

	rr := httptest.NewRecorder()
	handler := http.HandlerFunc(server.SystemMessageHandler)
	handler.ServeHTTP(rr, req)

	assert.Equal(t, http.StatusOK, rr.Code)
	assert.True(t, getMessagesCalled, "Expected GetSystemMessages to be called")
}

func Test_SaveButtonSavesToParameterStore(t *testing.T) {
	t.Parallel()

	form := url.Values{}
	form.Add("use-eng", "Updated message")

	mockSysMsgService := &mockSystemMessageService{
		putMessagesFunc: func(ctx context.Context, messages map[string]string) (bool, error) {
			assert.Equal(t, "Updated message", messages["use-eng"])
			return false, nil
		},
	}

	mockTemplateService := &mockTemplateWriterService{
		RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, templateName string, data interface{}) error {
			return nil
		},
	}

	server := handlers.NewSystemMessageServer(mockSysMsgService, mockTemplateService)

	req, err := http.NewRequest("POST", "/some-url", strings.NewReader(form.Encode()))
	assert.NoError(t, err)

	req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
	rr := httptest.NewRecorder()

	handler := http.HandlerFunc(server.SystemMessageHandler)
	handler.ServeHTTP(rr, req)

	assert.Equal(t, http.StatusOK, rr.Code)
}

func Test_SystemMessageHandlerParseFormError(t *testing.T) {
	t.Parallel()

	mockSysMsgService := &mockSystemMessageService{}
	mockTemplateService := &mockTemplateWriterService{}

	server := handlers.NewSystemMessageServer(mockSysMsgService, mockTemplateService)

	invalidFormData := strings.NewReader("%")
	req, err := http.NewRequest("POST", "/some-url", invalidFormData)
	assert.NoError(t, err)

	req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
	rr := httptest.NewRecorder()

	handler := http.HandlerFunc(server.SystemMessageHandler)
	handler.ServeHTTP(rr, req)

	assert.Equal(t, http.StatusBadRequest, rr.Code)
}
