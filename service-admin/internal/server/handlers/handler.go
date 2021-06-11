package handlers

import (
	"context"
	"errors"
	"fmt"
	"html/template"
	"io"
	"net/http"

	"github.com/rs/zerolog/log"
)

type ContextKey string

type Template interface {
	ExecuteTemplate(io.Writer, string, interface{}) error
}

type Templates interface {
	Get(name string) (*template.Template, error)
}

// TemplateContextKey defines the key under which the Templates live in a request context.
const TemplateContextKey ContextKey = "templates"

// ErrTemplateNotFound provides a static error that can be wrapped when template
// discovery fails.
var ErrTemplateNotFound = errors.New("template not found")

func GetTemplate(ctx context.Context, name string) (*template.Template, error) {
	i := ctx.Value(TemplateContextKey)

	t, is := i.(Templates)
	if !is {
		err := fmt.Errorf("%w, templates not found at context value", ErrTemplateNotFound)
		log.Error().Err(err).Msg("")

		return nil, err
	}

	tmpl, err := t.Get(name)
	if err != nil {
		err := fmt.Errorf("%w, template \"%s\" not in available templates", err, name)
		log.Error().Err(err).Msg("")

		return nil, err
	}

	return tmpl, nil
}

func RenderTemplate(w http.ResponseWriter, ctx context.Context, name string, data interface{}) error {
	template, err := GetTemplate(ctx, name)
	if err != nil {
		return err
	}

	if err = template.ExecuteTemplate(w, "default", data); err != nil {
		log.Err(err).Msg("error whilst rendering template")
		return err
	}

	return nil
}
