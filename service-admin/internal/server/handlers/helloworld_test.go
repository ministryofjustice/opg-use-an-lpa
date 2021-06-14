package handlers_test

import (
	"context"
	"net/http/httptest"
	"testing"

	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
)

func TestHelloHandler(t *testing.T) {
	t.Parallel()

	rw := httptest.NewRecorder()

	rq := httptest.NewRequest("", "/", nil)
	rq = rq.WithContext(context.WithValue(context.Background(), TemplateContextKey, &mockTemplates{}))

	handler := HelloHandler()

	// will fail to find template in mockTemplates so tests failure case at this time
	handler(rw, rq)
}
