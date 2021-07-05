package server

import (
	"context"
	"errors"
	"fmt"
	"html/template"
	"io/fs"
	"net/http"
	"path/filepath"
	"time"

	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/rs/zerolog/log"
)

type Templates struct {
	tmpls map[string]*template.Template
}

var ErrTemplateNotFound = errors.New("template not found")

func (t *Templates) Get(name string) (*template.Template, error) {
	if tpl, isMapContains := t.tmpls[name]; isMapContains {
		return tpl, nil
	} else {
		return nil, fmt.Errorf("%w, \"%s\"", ErrTemplateNotFound, name)
	}
}

func WithTemplates(next http.Handler, t *Templates) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		ctx := r.Context()
		ctx = context.WithValue(ctx, handlers.TemplateContextKey{}, t)

		next.ServeHTTP(w, r.WithContext(ctx))
	})
}

func LoadTemplates(folder fs.FS) *Templates {
	t := template.New("")
	t = t.Funcs(template.FuncMap{
		"readableDateTime": readableDateTime,
	})

	files, err := fs.Glob(folder, "*.page.gohtml")
	if err != nil {
		log.Fatal().AnErr("error", err).Msg("unable to glob page templates")
	}

	tmpls := make(map[string]*template.Template)

	for _, file := range files {
		name := filepath.Base(file)

		ts, err := template.Must(t.Clone()).ParseFS(folder, file)
		if err != nil {
			log.Fatal().AnErr("error", err).Msgf("unable to load template %s", file)
		}

		ts, err = ts.ParseFS(folder, "*.layout.gohtml")
		if err != nil {
			log.Info().AnErr("error", err).Msg("unable to glob layouts")
		}

		ts, err = ts.ParseFS(folder, "*.partial.gohtml")
		if err != nil {
			log.Info().AnErr("error", err).Msg("unable to glob partials")
		}

		tmpls[name] = ts
	}

	return &Templates{
		tmpls: tmpls,
	}
}

func readableDateTime(date string) string {
	t, err := time.Parse(time.RFC3339, date)
	if err != nil {
		return date
	}

	return t.Format("2 January 2006 at 3:04PM")
}
