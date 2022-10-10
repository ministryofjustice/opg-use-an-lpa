package handlers_test

import (
	"testing"
	"net/http"
	"context"
	"net/http/httptest"
	"strings"
	"errors"

	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
)

type mockTemplateService struct {
	RenderTemplateFunc func(http.ResponseWriter, context.Context, string, interface{}) error
}

func (m *mockTemplateWriterService) RenderStatsTemplate(w http.ResponseWriter, ctx context.Context, name string, data interface{}) error {
	return m.RenderTemplateFunc(w, ctx, name, data)
}

func TestTemplateErrorPanic(t *testing.T) {
	t.Parallel()

	t.Run("Template error ends in panic", func(t *testing.T) {
		t.Parallel()

		ts := &mockTemplateWriterService{
			RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, s string, i interface{}) error {
				return errors.New("I have errored")
			},
		}

		server := NewStatsServer(ts)
		reader := strings.NewReader("")
		var req *http.Request

		req, _ = http.NewRequest("GET", "/my_url", reader)

		req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
		w := httptest.NewRecorder()

		//recover panic
		defer func() { _ = recover() }()

		server.StatsHandler(w, req)

		t.Errorf("did not panic")

	})
}