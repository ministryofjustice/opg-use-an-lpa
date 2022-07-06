package server

import (
	"context"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/gorilla/mux"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
)

type mockTemplateWriterService struct {
	RenderTemplateFunc func(http.ResponseWriter, context.Context, string, interface{}) error
}

func (m *mockTemplateWriterService) RenderTemplate(w http.ResponseWriter, ctx context.Context, template string, data interface{}) error {
	if m.RenderTemplateFunc != nil {
		return m.RenderTemplateFunc(w, ctx, template, data)
	}

	return nil
}
func Test_withErrorHandling(t *testing.T) {
	type args struct {
		next http.Handler
	}

	tests := []struct {
		name               string
		args               args
		wantedTemplateName string
	}{
		{
			name: "Error handling added successfully",
			args: args{
				next: http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) { w.WriteHeader(400) }),
			},

			wantedTemplateName: "error.page.gohtml",
		},
		{
			name: "404 Error handling added successfully",
			args: args{
				next: http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) { w.WriteHeader(404) }),
			},

			wantedTemplateName: "notfound.page.gohtml",
		},
		{
			name: "403 Error handling added successfully",
			args: args{
				next: http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) { w.WriteHeader(403) }),
			},

			wantedTemplateName: "notauthorised.page.gohtml",
		},
		{
			name: "recover from panic",
			args: args{
				next: http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) { panic("") }),
			},

			wantedTemplateName: "error.page.gohtml",
		},
	}
	for _, tt := range tests {
		tw := &mockTemplateWriterService{
			RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, s string, i interface{}) error {
				if s != tt.wantedTemplateName {
					t.Errorf("expected %v recieved %v", tt.wantedTemplateName, s)
				}
				return nil
			}}

		t.Run(tt.name, func(t *testing.T) {
			got := withErrorHandling(tt.args.next, tw)
			got.ServeHTTP(httptest.NewRecorder(), httptest.NewRequest("GET", "/", nil))

		})
	}
}

func Test_app_InitialiseServer(t *testing.T) {
	type fields struct {
		db *dynamodb.Client
		r  *mux.Router
		tw handlers.TemplateWriterService
	}
	type args struct {
		keyURL string
	}
	tests := []struct {
		name   string
		fields fields
		args   args
		want   http.Handler
	}{
		{
			name: "test",
			fields: fields{
				db: data.NewDynamoConnection("", "", ""),
				r:  mux.NewRouter(),
				tw: &mockTemplateWriterService{},
			},
			args: args{""},
		},
	}
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			a := &app{
				db: tt.fields.db,
				r:  tt.fields.r,
				tw: tt.fields.tw,
			}
			a.InitialiseServer(tt.args.keyURL)
		})
	}
}
