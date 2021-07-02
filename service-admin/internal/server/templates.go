package server

import (
	"context"
	"errors"
	"fmt"
	"html/template"
	"io/fs"
	"net/http"
	"time"

	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/rs/zerolog/log"
)

type Templates struct {
	tmpls *template.Template
}

var ErrTemplateNotFound = errors.New("template not found")

func (t *Templates) Get(name string) (*template.Template, error) {
	if tpl := t.tmpls.Lookup(name); tpl != nil {
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
		"readableDateTime": func(date string) string {
			t, err := time.Parse(time.RFC3339, date)
			if err != nil {
				return date
			}

			return t.Format("2 January 2006 at 3:04PM")
		},
	})

	tmpls, err := t.ParseFS(folder, "*.gohtml")
	if err != nil {
		log.Fatal().AnErr("error", err).Msg("unable to glob template folder")
	}

	return &Templates{
		tmpls: tmpls,
	}
}
