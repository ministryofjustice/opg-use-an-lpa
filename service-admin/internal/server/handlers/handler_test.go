package handlers_test

import (
	"context"
	"errors"
	"html/template"
	"testing"

	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
	"github.com/stretchr/testify/assert"
)

type mockTemplates struct{}

func (m *mockTemplates) Get(name string) (*template.Template, error) {
	if name == "test" {
		return &template.Template{}, nil
	} else {
		return nil, errors.New("template not found")
	}
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
			want:    &template.Template{},
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
