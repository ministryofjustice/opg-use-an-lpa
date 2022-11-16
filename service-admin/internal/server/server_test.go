package server_test

import (
	"context"
	"errors"
	"net/http"
	"net/http/httptest"
	"net/url"
	"testing"

	"github.com/aws/aws-sdk-go-v2/service/dynamodb"

	"github.com/gorilla/mux"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/stretchr/testify/assert"
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

type mockActivationKeyService struct {
	GetActivationKeyFromCodesFunc func(context.Context, string) (*[]data.ActivationKey, error)
}

func (m *mockActivationKeyService) GetActivationKeyFromCodes(ctx context.Context, key string) (*[]data.ActivationKey, error) {
	if m.GetActivationKeyFromCodesFunc != nil {
		return m.GetActivationKeyFromCodes(ctx, key)
	}

	return nil, nil
}

type mockDynamoDBClient struct {
	QueryFunc   func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
	GetItemFunc func(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error)
	BatchGetItemFunc func(ctx context.Context, params *dynamodb.BatchGetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.BatchGetItemOutput, error)
}

func (m *mockDynamoDBClient) Query(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
	return m.QueryFunc(ctx, params, optFns...)
}

func (m *mockDynamoDBClient) GetItem(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error) {
	return m.GetItemFunc(ctx, params, optFns...)
}

func (m *mockDynamoDBClient) BatchGetItem(ctx context.Context, params *dynamodb.BatchGetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.BatchGetItemOutput, error) {
  return m.BatchGetItemFunc(ctx, params, optFns...)
}

func Test_withErrorHandling(t *testing.T) {
	t.Parallel()

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
		{
			name: "writing ok header does not error",
			args: args{
				next: http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) { w.WriteHeader(200) }),
			},
			wantedTemplateName: "",
		},
	}
	for _, tt := range tests {
		tt := tt
		tw := &mockTemplateWriterService{
			RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, s string, i interface{}) error {
				if s != tt.wantedTemplateName {
					t.Errorf("expected %v received %v", tt.wantedTemplateName, s)
				}
				return nil
			}}

		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()
			got := server.WithErrorHandling(tt.args.next, tw)
			got.ServeHTTP(httptest.NewRecorder(), httptest.NewRequest("GET", "/", nil))

		})
	}
}

func Test_withErrorHandlingWriter(t *testing.T) {
	t.Parallel()

	type args struct {
		next http.Handler
	}

	tests := []struct {
		name               string
		args               args
		wantedTemplateName string
		expected           string
	}{
		{
			name: "writing ok header still writes correctly",
			args: args{
				next: http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
					w.WriteHeader(200)
					_, err := w.Write([]byte("This body has been written"))
					if err != nil {
						t.Errorf("Error writing body")
					}
				}),
			},
			wantedTemplateName: "",
			expected:           "This body has been written",
		},
		{
			name: "error header no longer outputs normal written material",
			args: args{
				next: http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
					w.WriteHeader(400)
					_, err := w.Write([]byte("This body has been written"))
					if err != nil {
						t.Errorf("Error writing body")
					}
				}),
			},
			wantedTemplateName: "error.page.gohtml",
			expected:           "",
		},
	}
	for _, tt := range tests {
		tt := tt
		tw := &mockTemplateWriterService{
			RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, s string, i interface{}) error {
				if s != tt.wantedTemplateName {
					t.Errorf("expected %v received %v", tt.wantedTemplateName, s)
				}
				return nil
			}}

		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			recorder := httptest.NewRecorder()
			got := server.WithErrorHandling(tt.args.next, tw)
			got.ServeHTTP(recorder, httptest.NewRequest("GET", "/", nil))

			if recorder.Body.String() != tt.expected {
				t.Errorf("expected %v received %v", tt.expected, recorder.Body.String())
			}
		})
	}
}

func Test_withErrorHandlingTemplateError(t *testing.T) {
	t.Parallel()

	type args struct {
		next http.Handler
	}

	tests := []struct {
		name           string
		args           args
		expectedStatus int
	}{
		{
			name: "template error causes panic",
			args: args{
				next: http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) { w.WriteHeader(404) }),
			},
			expectedStatus: 500,
		},
	}
	for _, tt := range tests {
		tt := tt
		tw := &mockTemplateWriterService{
			RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, s string, i interface{}) error {
				if s == "notfound.page.gohtml" {
					return errors.New("")
				}
				return nil
			}}

		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			got := server.WithErrorHandling(tt.args.next, tw)
			assert.HTTPStatusCode(t, got.ServeHTTP, "GET", "/", nil, tt.expectedStatus)

		})
	}
}

func Test_app_InitialiseServer(t *testing.T) {
	t.Parallel()

	type fields struct {
		db data.DynamoConnection
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
				db: data.DynamoConnection{
					Client: &mockDynamoDBClient{},
				},
				r:  mux.NewRouter(),
				tw: &mockTemplateWriterService{},
			},
			args: args{""},
		},
	}

	for _, tt := range tests {
		tt := tt
		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			tt.fields.r.Handle("/hello", http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {}))
			a := server.NewAdminApp(tt.fields.db, tt.fields.r, tt.fields.tw, &mockActivationKeyService{})
			handler := a.InitialiseServer(tt.args.keyURL, &url.URL{})
			assert.HTTPStatusCode(t, handler.ServeHTTP, "GET", "/hello", nil, 200)
		})
	}
}
