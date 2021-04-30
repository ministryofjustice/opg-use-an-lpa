package server

import (
	"io"
	"net/http"
	"net/http/httptest"
	"testing"

	"gotest.tools/assert"
)

type mockTemplate struct {
}

func (m *mockTemplate) ExecuteTemplate(w io.Writer, name string, data interface{}) error {
	return nil
}

func TestHelloHandlerReturnsStatusOK(t *testing.T) {
	handler := HelloHandler(&mockTemplate{})

	w := httptest.NewRecorder()
	r := httptest.NewRequest(http.MethodGet, "/", nil)

	handler(w, r)

	resp := w.Result()
	assert.Equal(t, resp.StatusCode, http.StatusOK)
}

func TestHelloHandlerLoginRequest(t *testing.T) {
	handler := HelloHandler(&mockTemplate{})

	w := httptest.NewRecorder()
	r := httptest.NewRequest(http.MethodPost, "/", nil)
	r.ParseForm()
	r.Form.Set("Username", "Josh")

	handler(w, r)

	resp := w.Result()
	assert.Equal(t, resp.StatusCode, http.StatusFound)
}

func TestHelloHandlerLoginRequestWithExpectedValues(t *testing.T) {
	handler := HelloHandler(&mockTemplate{})

	w := httptest.NewRecorder()
	r := httptest.NewRequest(http.MethodPost, "/", nil)
	r.ParseForm()
	r.Form.Set("Email", "someone@somewhere.co.uk")
	r.Form.Set("Password", "Password123")

	handler(w, r)

	resp := w.Result()
	assert.Equal(t, resp.StatusCode, http.StatusOK)
}
