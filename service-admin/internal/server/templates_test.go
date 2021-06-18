package server_test

import (
	"html/template"
	"io/fs"
	"net/http"
	"testing"

	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
	"github.com/spf13/afero"
	"github.com/stretchr/testify/assert"
)

func testFS() (fs.FS, error) {
	fs := afero.NewMemMapFs()

	err := fs.MkdirAll("layouts", 0755)
	if err != nil {
		return nil, err
	}

	err = afero.WriteFile(fs, "layouts/default.gohtml", []byte("{{ define \"default\" }}{{ block \"main\" . }}{{ end }}{{ end }}"), 0644)
	if err != nil {
		return nil, err
	}

	err = afero.WriteFile(fs, "test.gohtml", []byte("{{ template \"default\" . }}{{ define \"main\"}}Hello World{{ end }}"), 0644)
	if err != nil {
		return nil, err
	}

	return afero.NewIOFS(fs), nil
}

func TestLoadTemplates(t *testing.T) {
	t.Parallel()

	// nop the logger so panic and exit calls (if any) don't do anything.
	log.Logger = zerolog.Nop()

	fs, err := testFS()
	if err != nil {
		t.Errorf("unable to create in memory template filesystem, %w", err)
	}

	ts := LoadTemplates(fs)

	_, err = ts.Get("test")
	if err != nil {
		t.Error("failed to load expected template")
	}
}

func TestTemplates_Get(t *testing.T) {
	t.Parallel()

	fs, err := testFS()
	if err != nil {
		t.Errorf("unable to create in memory template filesystem, %w", err)
	}

	tests := []struct {
		name     string
		tmpls    *Templates
		tmplName string
		wantErr  bool
	}{
		{
			name:     "gets a template that exists",
			tmpls:    LoadTemplates(fs),
			tmplName: "test",
			wantErr:  false,
		},
		{
			name:     "returns an error when template does not exist",
			tmpls:    LoadTemplates(fs),
			tmplName: "notfound",
			wantErr:  true,
		},
	}
	for _, tt := range tests {
		tt := tt

		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			got, err := tt.tmpls.Get(tt.tmplName)
			if err != nil {
				if !tt.wantErr {
					t.Errorf("Templates.Get() error = %v, wantErr %v", err, tt.wantErr)
					return
				}

				assert.ErrorIs(t, err, ErrTemplateNotFound, "Error is not \"ErrTemplateNotFound\"")
			}

			assert.IsType(t, &template.Template{}, got, "Templates.Get() = %v, want %v", got, &template.Template{})
		})
	}
}

func TestWithTemplates(t *testing.T) {
	t.Parallel()

	next := http.HandlerFunc(func(rw http.ResponseWriter, r *http.Request) {
		// check templates have been attached to request context
		v := r.Context().Value(handlers.TemplateContextKey{})
		assert.IsType(t, &Templates{}, v)
		rw.WriteHeader(200)
	})

	fs, err := testFS()
	if err != nil {
		t.Errorf("unable to create in memory template filesystem, %w", err)
	}

	sut := WithTemplates(next, LoadTemplates(fs))

	assert.HTTPSuccess(t, sut.ServeHTTP, "GET", "/", nil, "handler not successfully running")
}
