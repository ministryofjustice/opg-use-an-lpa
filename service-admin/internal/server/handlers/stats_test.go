package handlers_test

import (
	"os"
	"testing"

	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server"
	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/spf13/afero"
	"github.com/stretchr/testify/assert"
)

func TestStatsHandler(t *testing.T) {
	t.Parallel()

	handler := server.WithTemplates(
		StatsHandler(),
		server.LoadTemplates(os.DirFS("../../../web/templates")),
	)

	assert.HTTPSuccess(t, handler.ServeHTTP, "GET", "/stats", nil)
	assert.HTTPBodyContains(t, handler.ServeHTTP, "GET", "/stats", nil, "Measuring Impact")
}

func StatsHandlerWithBadTemplate(t *testing.T) {
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
		StatsHandler(),
		server.LoadTemplates(fs), // bad template location loads no templates
	)

	// the handler panics but that is handled upstream so it claims success at this point
	assert.HTTPSuccess(t, handler.ServeHTTP, "GET", "/stats", nil)

	t.Errorf("did not panic")
}
