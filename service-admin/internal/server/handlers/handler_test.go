package handlers_test

import (
	"context"
	"errors"
	"html/template"
	"net/http"
	"net/http/httptest"
	"testing"

	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
	"github.com/stretchr/testify/assert"
)

type mockTemplates struct{}

func (m *mockTemplates) Get(name string) (*template.Template, error) {
	if name == "test" {
		return mockTemplate(), nil
	} else if name == "badlayout" {
		return template.New("").Parse("Template Content")
	} else {
		return nil, errors.New("template not found")
	}
}

func mockTemplate() *template.Template {
	t, err := template.New("default").Parse("Template Content")
	if err != nil {
		panic("creation of mock template failed")
	}

	return t
}

func TestGetTemplate(t *testing.T) {
	t.Parallel()

	log.Logger = zerolog.Nop()

	type args struct {
		ctx  context.Context
		name string
	}

	tests := []struct {
		name    string
		args    args
		want    *template.Template
		wantErr bool
	}{
		{
			name: "it finds a named template",
			args: args{
				ctx:  context.WithValue(context.Background(), TemplateContextKey, &mockTemplates{}),
				name: "test",
			},
			want:    mockTemplate(),
			wantErr: false,
		},
		{
			name: "it errors when context not appropriately set",
			args: args{
				ctx:  context.Background(),
				name: "test",
			},
			want:    nil,
			wantErr: true,
		},
		{
			name: "it errors when template not in context templates",
			args: args{
				ctx:  context.WithValue(context.Background(), TemplateContextKey, &mockTemplates{}),
				name: "notfound",
			},
			want:    nil,
			wantErr: true,
		},
	}

	for _, tt := range tests {
		tt := tt

		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			got, err := GetTemplate(tt.args.ctx, tt.args.name)
			if (err != nil) != tt.wantErr {
				t.Errorf("Unexpected error from GetTemplate(), got %v, want %v", (err != nil), tt.wantErr)
			}

			assert.Equal(t, got, tt.want, "GetTemplate() = %v, want %v", got, tt.want)
		})
	}
}

func TestRenderTemplate(t *testing.T) {
	t.Parallel()

	log.Logger = zerolog.Nop()

	type args struct {
		w    http.ResponseWriter
		ctx  context.Context
		name string
		data interface{}
	}

	tests := []struct {
		name    string
		args    args
		wantErr bool
	}{
		{
			name: "it successfully renders a given template",
			args: args{
				w:    httptest.NewRecorder(),
				ctx:  context.WithValue(context.Background(), TemplateContextKey, &mockTemplates{}),
				name: "test",
				data: nil,
			},
			wantErr: false,
		},
		{
			name: "it errors when template not in context templates",
			args: args{
				w:    httptest.NewRecorder(),
				ctx:  context.WithValue(context.Background(), TemplateContextKey, &mockTemplates{}),
				name: "notfound",
				data: nil,
			},
			wantErr: true,
		},
		{
			name: "it errors when layout not in template",
			args: args{
				w:    httptest.NewRecorder(),
				ctx:  context.WithValue(context.Background(), TemplateContextKey, &mockTemplates{}),
				name: "badlayout",
				data: nil,
			},
			wantErr: true,
		},
	}

	for _, tt := range tests {
		tt := tt

		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			if err := RenderTemplate(tt.args.w, tt.args.ctx, tt.args.name, tt.args.data); (err != nil) != tt.wantErr {
				t.Errorf("RenderTemplate() error = %v, wantErr %v", err, tt.wantErr)
			}
		})
	}
}
