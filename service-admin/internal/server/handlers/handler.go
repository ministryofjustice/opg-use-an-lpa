package handlers

import (
	"context"
	"html/template"
	"io"

	"github.com/rs/zerolog/log"
)

type ContextKey string

type Template interface {
	ExecuteTemplate(io.Writer, string, interface{}) error
}

type Templates interface {
	Get(name string) (*template.Template, error)
}

var TemplateContextKey ContextKey = "templates"

func GetTemplate(ctx context.Context, name string) *template.Template {
	i := ctx.Value(TemplateContextKey)
	t, is := i.(Templates)
	if !is {
		log.Fatal().Msg("templates not loaded in correct context key")
	}

	tmpl, err := t.Get(name)
	if err != nil {
		log.Fatal().AnErr("error", err).Msg("unable to find template in context templates")
	}

	return tmpl
}
