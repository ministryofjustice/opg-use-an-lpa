package handlers_test

import (
	"context"
	"fmt"
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
	putMessagesFunc func(ctx context.Context, messages map[string]string) (bool, bool, error)
}

func (m *mockSystemMessageService) GetSystemMessages(ctx context.Context) (map[string]string, error) {
	return m.getMessagesFunc(ctx)
}

func (m *mockSystemMessageService) PutSystemMessages(ctx context.Context, messages map[string]string) (bool, bool, error) {
	return m.putMessagesFunc(ctx, messages)
}

func Test_RenderTemplateLoadsFromParameterStore(t *testing.T) {
	t.Parallel()

	mockSysMsgService := &mockSystemMessageService{
		getMessagesFunc: func(ctx context.Context) (map[string]string, error) {
			return map[string]string{
				"/system-message/use/en":  "use hello world en",
				"/system-message/use/cy":  "use helo byd",
				"/system-message/view/en": "view hello world",
				"/system-message/view/cy": "view helo byd",
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

	assert.Equal(t, http.StatusOK, rr.Code, "Handler returned wrong status code for GET request")
	assert.True(t, getMessagesCalled, "GetSystemMessages was not called in GET request")
}

func Test_SaveButtonSavesToParameterStore(t *testing.T) {
	t.Parallel()

	form := url.Values{}
	form.Add("use-eng", "Updated message")

	mockSysMsgService := &mockSystemMessageService{
		putMessagesFunc: func(ctx context.Context, messages map[string]string) (bool, bool, error) {
			assert.Equal(t, "Updated message", messages["/system-message/use/en"])
			return true, false, nil
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

	assert.Equal(t, http.StatusOK, rr.Code, "Handler did not return OK status on successful POST")
}

func Test_SystemMessageHandler_PutSystemMessagesError(t *testing.T) {
	t.Parallel()

	mockSysMsgService := &mockSystemMessageService{
		putMessagesFunc: func(ctx context.Context, messages map[string]string) (bool, bool, error) {
			return false, false, fmt.Errorf("update error")
		},
	}

	mockTemplateService := &mockTemplateWriterService{
		RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, templateName string, data interface{}) error {
			return nil
		},
	}

	server := handlers.NewSystemMessageServer(mockSysMsgService, mockTemplateService)

	req, err := http.NewRequest("POST", "/some-url", nil)
	assert.NoError(t, err)
	req.Header.Add("Content-Type", "application/x-www-form-urlencoded")

	rr := httptest.NewRecorder()
	handler := http.HandlerFunc(server.SystemMessageHandler)
	handler.ServeHTTP(rr, req)

	assert.Equal(t, 400, rr.Code, "Handler did not return the expected error status when PutSystemMessages returns an error")
}

func Test_SystemMessageHandler_PutSystemMessagesDeleted(t *testing.T) {
	t.Parallel()

	mockSysMsgService := &mockSystemMessageService{
		putMessagesFunc: func(ctx context.Context, messages map[string]string) (bool, bool, error) {
			return false, true, nil
		},
	}

	var capturedTemplateData *handlers.SystemMessageData

	mockTemplateService := &mockTemplateWriterService{
		RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, templateName string, data interface{}) error {
			if td, ok := data.(handlers.SystemMessageData); ok {
				capturedTemplateData = &td
			}
			return nil
		},
	}

	server := handlers.NewSystemMessageServer(mockSysMsgService, mockTemplateService)

	form := url.Values{}
	form.Add("use-eng", "") // simulate deletion
	form.Add("use-cy", "")  // simulate deletion
	req, err := http.NewRequest("POST", "/some-url", strings.NewReader(form.Encode()))
	assert.NoError(t, err)
	req.Header.Add("Content-Type", "application/x-www-form-urlencoded")

	rr := httptest.NewRecorder()
	handler := http.HandlerFunc(server.SystemMessageHandler)
	handler.ServeHTTP(rr, req)

	assert.Equal(t, http.StatusOK, rr.Code, "Handler did not return OK status when message was deleted")
	assert.NotNil(t, capturedTemplateData, "Template data was not set")
	assert.NotNil(t, capturedTemplateData.SuccessMessage, "Expected success message not to be nil")
	assert.Equal(t, "System message has been removed", *capturedTemplateData.SuccessMessage)
}

func Test_SystemMessageHandler_PutSystemMessagesUpdated(t *testing.T) {
	t.Parallel()

	mockSysMsgService := &mockSystemMessageService{
		putMessagesFunc: func(ctx context.Context, messages map[string]string) (bool, bool, error) {
			return true, false, nil
		},
	}

	var capturedTemplateData *handlers.SystemMessageData

	mockTemplateService := &mockTemplateWriterService{
		RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, templateName string, data interface{}) error {
			if td, ok := data.(handlers.SystemMessageData); ok {
				capturedTemplateData = &td
			}
			return nil
		},
	}

	server := handlers.NewSystemMessageServer(mockSysMsgService, mockTemplateService)

	form := url.Values{}
	form.Add("use-eng", "Updated message")
	form.Add("use-cy", "Welsh message")
	req, err := http.NewRequest("POST", "/some-url", strings.NewReader(form.Encode()))
	assert.NoError(t, err)
	req.Header.Add("Content-Type", "application/x-www-form-urlencoded")

	rr := httptest.NewRecorder()
	handler := http.HandlerFunc(server.SystemMessageHandler)
	handler.ServeHTTP(rr, req)

	assert.Equal(t, http.StatusOK, rr.Code, "Handler did not return OK status on successful message update")
	assert.NotNil(t, capturedTemplateData, "Template data was not set")
	assert.NotNil(t, capturedTemplateData.SuccessMessage, "Expected success message not to be nil")
	assert.Equal(t, "System message has been updated", *capturedTemplateData.SuccessMessage)
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

	assert.Equal(t, http.StatusBadRequest, rr.Code, "Handler did not return BadRequest status on form parse error")
}

func Test_SystemMessageHandler_TemplateRenderingError(t *testing.T) {
	t.Parallel()

	mockSysMsgService := &mockSystemMessageService{
		getMessagesFunc: func(ctx context.Context) (map[string]string, error) {
			return nil, fmt.Errorf("error retrieving messages")
		},
	}

	mockTemplateService := &mockTemplateWriterService{
		RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, templateName string, data interface{}) error {
			return fmt.Errorf("template rendering error")
		},
	}

	server := handlers.NewSystemMessageServer(mockSysMsgService, mockTemplateService)

	req, err := http.NewRequest("GET", "/some-url", strings.NewReader("moon"))
	assert.NoError(t, err)

	rr := httptest.NewRecorder()
	handler := http.HandlerFunc(server.SystemMessageHandler)
	handler.ServeHTTP(rr, req)

	assert.Equal(t, http.StatusInternalServerError, rr.Code, "Handler did not return InternalServerError on template rendering error")
}

func Test_SystemMessageHandler_GetSystemMessagesError(t *testing.T) {
	t.Parallel()

	var capturedTemplateData *handlers.SystemMessageData

	mockSysMsgService := &mockSystemMessageService{
		getMessagesFunc: func(ctx context.Context) (map[string]string, error) {
			return nil, fmt.Errorf("error retrieving messages")
		},
	}

	mockTemplateService := &mockTemplateWriterService{
		RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, templateName string, data interface{}) error {
			// Type assert data to *handlers.SystemMessageData and capture it
			if td, ok := data.(handlers.SystemMessageData); ok {
				capturedTemplateData = &td
			}
			return nil
		},
	}

	server := handlers.NewSystemMessageServer(mockSysMsgService, mockTemplateService)

	req, err := http.NewRequest("GET", "/some-url", nil)
	assert.NoError(t, err)

	rr := httptest.NewRecorder()
	handler := http.HandlerFunc(server.SystemMessageHandler)
	handler.ServeHTTP(rr, req)

	assert.NotNil(t, capturedTemplateData, "Template data was not set")
	assert.NotNil(t, capturedTemplateData.ErrorMessage, "Error message was not set")

	expectedError := "Error retrieving system messages"
	assert.Equal(t, *capturedTemplateData.ErrorMessage, expectedError, "Handler did not return InternalServerError when unable to retrieve system messages")
}

func Test_SystemMessageHandler_PostRequest_ValidationError(t *testing.T) {
	t.Parallel()

	var capturedTemplateData *handlers.SystemMessageData

	mockSysMsgService := &mockSystemMessageService{
		putMessagesFunc: func(ctx context.Context, messages map[string]string) (bool, bool, error) {
			return false, false, nil
		},
	}
	mockTemplateService := &mockTemplateWriterService{
		RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, templateName string, data interface{}) error {
			// Type assert data to *handlers.SystemMessageData and capture it
			if td, ok := data.(handlers.SystemMessageData); ok {
				capturedTemplateData = &td
			}
			return nil
		},
	}

	server := handlers.NewSystemMessageServer(mockSysMsgService, mockTemplateService)

	form := url.Values{}
	form.Add("use-eng", "English message")

	req, err := http.NewRequest("POST", "/some-url", strings.NewReader(form.Encode()))
	assert.NoError(t, err)
	req.Header.Add("Content-Type", "application/x-www-form-urlencoded")

	rr := httptest.NewRecorder()
	handler := http.HandlerFunc(server.SystemMessageHandler)
	handler.ServeHTTP(rr, req)

	assert.NotNil(t, capturedTemplateData, "Template data was not set")
	assert.NotNil(t, capturedTemplateData.ErrorMessage, "Error message was not set")
	assert.Equal(t, *capturedTemplateData.ErrorMessage, "Both English and Welsh versions are required for each message")
}

func Test_SystemMessageHandler_PostRequest_NoValidationErrors(t *testing.T) {
	t.Parallel()

	var capturedTemplateData *handlers.SystemMessageData

	mockSysMsgService := &mockSystemMessageService{
		putMessagesFunc: func(ctx context.Context, messages map[string]string) (bool, bool, error) {
			return true, false, nil
		},
	}
	mockTemplateService := &mockTemplateWriterService{
		RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, templateName string, data interface{}) error {
			if td, ok := data.(handlers.SystemMessageData); ok {
				capturedTemplateData = &td
			}
			return nil
		},
	}

	server := handlers.NewSystemMessageServer(mockSysMsgService, mockTemplateService)

	form := url.Values{}
	form.Add("use-eng", "English message")
	form.Add("use-cy", "Welsh message")

	req, err := http.NewRequest("POST", "/some-url", strings.NewReader(form.Encode()))
	assert.NoError(t, err)
	req.Header.Add("Content-Type", "application/x-www-form-urlencoded")

	rr := httptest.NewRecorder()
	handler := http.HandlerFunc(server.SystemMessageHandler)
	handler.ServeHTTP(rr, req)

	assert.Nil(t, capturedTemplateData.ErrorMessage)
	assert.NotNil(t, capturedTemplateData.SuccessMessage)
	assert.Equal(t, *capturedTemplateData.SuccessMessage, "System message has been updated")

	mockSysMsgService.putMessagesFunc = func(ctx context.Context, messages map[string]string) (bool, bool, error) {
		return false, false, fmt.Errorf("mock failure")
	}

	handler.ServeHTTP(rr, req)

	assert.NotNil(t, capturedTemplateData.ErrorMessage)
	assert.Equal(t, *capturedTemplateData.ErrorMessage, "Error updating system messages")

	mockSysMsgService.putMessagesFunc = func(ctx context.Context, messages map[string]string) (bool, bool, error) {
		return false, true, nil
	}

	handler.ServeHTTP(rr, req)

	assert.Nil(t, capturedTemplateData.ErrorMessage)
	assert.NotNil(t, capturedTemplateData.SuccessMessage)
	assert.Equal(t, *capturedTemplateData.SuccessMessage, "System message has been removed")
}
