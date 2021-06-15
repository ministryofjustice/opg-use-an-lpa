package handlers_test

import (
	"os"
	"testing"

	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server"
	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/stretchr/testify/assert"
)

func TestHelloHandler(t *testing.T) {
	t.Parallel()

	handler := server.WithTemplates(
		HelloHandler(),
		server.LoadTemplates(os.DirFS("../../../web/templates")),
	)

	assert.HTTPSuccess(t, handler.ServeHTTP, "GET", "/", nil)
	assert.HTTPBodyContains(t, handler.ServeHTTP, "GET", "/", nil, "Hello World")
}
