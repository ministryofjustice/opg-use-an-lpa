package handlers_test

import (
	"os"
	"testing"

	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server"
	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/spf13/afero"
	"github.com/stretchr/testify/assert"
)

func TestHelloHandler(t *testing.T) {
	t.Parallel()

	handler := server.WithTemplates(
		HelloHandler(),
		server.LoadTemplates(os.DirFS("../../../web/templates")),
	)

	assert.HTTPSuccess(t, handler.ServeHTTP, "GET", "/helloworld", nil)
	assert.HTTPBodyContains(t, handler.ServeHTTP, "GET", "/helloworld", nil, "Hello World")
}

func TestHelloHandler_WithBadTemplate(t *testing.T) {
	t.Parallel()

	memfs := afero.NewMemMapFs()

	err := afero.WriteFile(memfs, "test.page.gohtml", []byte(""), 0644)
	if err != nil {
		t.Fatalf("%v", err)
	}

	fs := afero.NewIOFS(memfs)

	//stop panic from failing tests
	defer func() { _ = recover() }()

	handler := server.WithTemplates(
		HelloHandler(),
		server.LoadTemplates(fs), // bad template location loads no templates
	)

	// the handler panics but that is handled upstream so it claims success at this point
	assert.HTTPSuccess(t, handler.ServeHTTP, "GET", "/helloworld", nil)

	t.Errorf("did not panic")
}
