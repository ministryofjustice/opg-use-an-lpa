package server

import (
	"context"
	"errors"
	"fmt"
	"html/template"
	"io/fs"
	"net/http"
	"path/filepath"

	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/rs/zerolog/log"
)

type Templates struct {
	tmpls map[string]*template.Template
}

var ErrTemplateNotFound = errors.New("template not found")

func WithTemplates(next http.Handler, t *Templates) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		ctx := r.Context()
		ctx = context.WithValue(ctx, handlers.TemplateContextKey, t)

		next.ServeHTTP(w, r.WithContext(ctx))
	})
}

func LoadTemplates(folder fs.FS) *Templates {
	layouts, err := template.New("").ParseFS(folder, "layouts/*.gohtml")
	if err != nil {
		log.Fatal().AnErr("error", err).Msg("unable to glob layout folder")
	}

	files, err := fs.Glob(folder, "*.gohtml")
	if err != nil {
		log.Fatal().AnErr("error", err).Msg("unable to glob template folder")
	}

	t := &Templates{
		tmpls: make(map[string]*template.Template),
	}

	for _, file := range files {
		t.tmpls[filepath.Base(file)] = template.Must(template.Must(layouts.Clone()).ParseFS(folder, file))
	}

	return t
}

func (t *Templates) Get(name string) (*template.Template, error) {
	if tmpl, isMapContains := t.tmpls[name]; isMapContains {
		return tmpl, nil
	} else {
		return nil, fmt.Errorf("%w, \"%s\"", ErrTemplateNotFound, name)
	}
}
