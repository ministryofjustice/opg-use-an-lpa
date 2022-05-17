package handlers_test

import (
	"context"
	"errors"
	"net/http"
	"net/http/httptest"
	"reflect"
	"strings"
	"testing"

	validation "github.com/go-ozzo/ozzo-validation"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
)

type mockAccountService struct {
	GetActorByUserEmailFunc func(context.Context, string) (*data.ActorUser, error)
	GetEmailByUserIDFunc    func(context.Context, string) (string, error)
}

func (m *mockAccountService) GetActorUserByEmail(ctx context.Context, email string) (*data.ActorUser, error) {
	if m.GetActorByUserEmailFunc != nil {
		return m.GetActorByUserEmailFunc(ctx, email)
	}

	return &data.ActorUser{ID: ""}, nil
}

func (m *mockAccountService) GetEmailByUserID(ctx context.Context, userID string) (string, error) {
	if m.GetEmailByUserIDFunc != nil {
		return m.GetEmailByUserIDFunc(ctx, userID)
	}

	return "", nil
}

type mockLPAService struct {
	GetLPAsByUserIDFunc        func(context.Context, string) ([]*data.LPA, error)
	GetLPAByActivationCodeFunc func(context.Context, string) (*data.LPA, error)
}

func (m *mockLPAService) GetLpasByUserID(ctx context.Context, userID string) ([]*data.LPA, error) {
	if m.GetLPAsByUserIDFunc != nil {
		return m.GetLPAsByUserIDFunc(ctx, userID)
	}

	return []*data.LPA{}, nil
}

func (m *mockLPAService) GetLPAByActivationCode(ctx context.Context, code string) (*data.LPA, error) {
	if m.GetLPAByActivationCodeFunc != nil {
		return m.GetLPAByActivationCodeFunc(ctx, code)
	}

	return nil, nil
}

func Test_doSearch(t *testing.T) {
	t.Parallel()

	testLPA := &data.LPA{
		SiriusUID: "700000000123",
		Added:     "Date Added",
		UserID:    "TestID",
	}

	type args struct {
		ctx            context.Context
		accountService AccountService
		lpaService     LPAService
		queryType      QueryType
		q              string
	}

	tests := []struct {
		name            string
		args            args
		templateService TemplateWriterService
		want            interface{}
	}{
		{
			name: "Test standard email query",
			args: args{
				ctx: context.TODO(),
				accountService: &mockAccountService{
					GetActorByUserEmailFunc: func(ctx context.Context, s string) (*data.ActorUser, error) {
						if s == "test@email.com" {
							return &data.ActorUser{
								ID:        "TestID",
								LastLogin: "TestTime",
								Email:     "test@email.com",
							}, nil
						}
						t.Errorf("expected test@email.com got %v", s)
						return nil, nil //Is there a better way to stop test executing here?
					},
				},
				lpaService: &mockLPAService{
					GetLPAsByUserIDFunc: func(ctx context.Context, s string) ([]*data.LPA, error) {
						return []*data.LPA{testLPA}, nil
					},
				},
				queryType: 0, //Email query
				q:         "test@email.com",
			},
			want: &data.ActorUser{ID: "TestID", Email: "test@email.com", LastLogin: "TestTime", LPAs: []*data.LPA{testLPA}},
		},
		{
			name: "Test email query with error on account lookup",
			args: args{
				ctx: context.TODO(),
				accountService: &mockAccountService{
					GetActorByUserEmailFunc: func(ctx context.Context, s string) (*data.ActorUser, error) {
						return nil, errors.New("this is an error")
					},
				},
				lpaService: &mockLPAService{},
				queryType:  0, //Email query
				q:          "test@email.com",
			},
			want: nil,
		},
		{
			name: "Test email query with error on LPA lookup",
			args: args{
				ctx:            context.TODO(),
				accountService: &mockAccountService{},
				lpaService: &mockLPAService{
					GetLPAsByUserIDFunc: func(ctx context.Context, s string) ([]*data.LPA, error) {
						return nil, errors.New("this is an error")
					},
				},
				queryType: 0, //Email query
				q:         "test@email.com",
			},
			want: nil,
		},
		{
			name: "Test email query with not found error on LPA lookup returns empty result not nil",
			args: args{
				ctx: context.TODO(),
				accountService: &mockAccountService{
					GetActorByUserEmailFunc: func(ctx context.Context, s string) (*data.ActorUser, error) {
						if s == "test@email.com" {
							return &data.ActorUser{
								ID:        "TestID",
								LastLogin: "TestTime",
								Email:     "test@email.com",
							}, nil
						}
						t.Errorf("expected test@email.com got %v", s)
						return nil, nil
					},
				},
				lpaService: &mockLPAService{
					GetLPAsByUserIDFunc: func(ctx context.Context, s string) ([]*data.LPA, error) {
						return nil, data.ErrUserLpaActorMapNotFound
					},
				},
				queryType: 0, //Email query
				q:         "test@email.com",
			},
			want: &data.ActorUser{ID: "TestID", Email: "test@email.com", LastLogin: "TestTime", LPAs: nil},
		},
		{
			name: "Test standard activation key query",
			args: args{
				ctx: context.TODO(),
				accountService: &mockAccountService{
					GetEmailByUserIDFunc: func(ctx context.Context, s string) (string, error) {
						return "test@email.com", nil
					},
				},
				lpaService: &mockLPAService{
					GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) {
						return testLPA, nil
					},
				},
				queryType: 1, //Key query
				q:         "WWFCCH41R123",
			},
			want: map[string]interface{}{"Activation key": "WWFCCH41R123", "Used": "Yes", "Email": "test@email.com", "LPA": testLPA.SiriusUID},
		},
		{
			name: "Test activation key query with email not found",
			args: args{
				ctx: context.TODO(),
				accountService: &mockAccountService{
					GetEmailByUserIDFunc: func(ctx context.Context, s string) (string, error) {
						return "", nil
					},
				},
				lpaService: &mockLPAService{
					GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) {
						return testLPA, nil
					},
				},
				queryType: 1, //Key query
				q:         "WWFCCH41R123",
			},
			want: map[string]interface{}{"Activation key": "WWFCCH41R123", "Used": "Yes", "Email": "Not Found", "LPA": testLPA.SiriusUID},
		},
		{
			name: "Test activation key query with error finding email result by a UID",
			args: args{
				ctx: context.TODO(),
				accountService: &mockAccountService{
					GetEmailByUserIDFunc: func(ctx context.Context, s string) (string, error) {
						return "", errors.New("Error")
					},
				},
				lpaService: &mockLPAService{
					GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) {
						return testLPA, nil
					},
				},
				queryType: 1, //Key query
				q:         "WWFCCH41R123",
			},
			want: nil,
		},
		{
			name: "Test activation key query with error finding activation code",
			args: args{
				ctx: context.TODO(),
				accountService: &mockAccountService{
					GetEmailByUserIDFunc: func(ctx context.Context, s string) (string, error) {
						return "test@email.com", nil
					},
				},
				lpaService: &mockLPAService{
					GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) {
						return nil, errors.New("This is a test error")
					},
				},
				queryType: 1, //Key query
				q:         "WWFCCH41R123",
			},
			want: nil,
		},
		{
			name: "Test correct characters are removed",
			args: args{
				ctx: context.TODO(),
				accountService: &mockAccountService{
					GetEmailByUserIDFunc: func(ctx context.Context, s string) (string, error) {
						return "test@email.com", nil
					},
				},
				lpaService: &mockLPAService{
					GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) {
						if s != "WWFCCH41R123" {
							t.Errorf("expected WWFCCH41R123 recieved %v", s)
						}
						return testLPA, nil
					},
				},
				queryType: 1, //Key query
				q:         "C-WWFCCH41-R123",
			},
			want: map[string]interface{}{"Activation key": "C-WWFCCH41-R123", "Used": "Yes", "Email": "test@email.com", "LPA": testLPA.SiriusUID},
		},
	}

	for _, tt := range tests {
		tt := tt
		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()
			if got := DoSearch(tt.args.ctx, tt.args.accountService, tt.args.lpaService, tt.args.queryType, tt.args.q); !reflect.DeepEqual(got, tt.want) {
				t.Errorf("doSearch() = %v, want %v", got, tt.want)
			}
		})
	}
}

type mockTemplateWriterService struct {
	RenderTemplateFunc func(http.ResponseWriter, context.Context, string, interface{}) error
}

func (m *mockTemplateWriterService) RenderTemplate(w http.ResponseWriter, ctx context.Context, name string, data interface{}) error {
	return m.RenderTemplateFunc(w, ctx, name, data)
}

func Test_SearchHandler(t *testing.T) {
	t.Parallel()

	testLPA := &data.LPA{
		SiriusUID: "700000000123",
		Added:     "Date Added",
		UserID:    "TestID",
	}

	type args struct {
		accountService AccountService
		lpaService     LPAService
		q              string
	}

	tests := []struct {
		name     string
		args     args
		expected interface{}
	}{
		{
			name: "Normal activation key query",
			args: args{
				accountService: &mockAccountService{
					GetEmailByUserIDFunc: func(ctx context.Context, s string) (string, error) { return "test@email.com", nil },
				},
				lpaService: &mockLPAService{GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) { return testLPA, nil }},

				q: "query=C-WWFCCH41R123",
			},
			expected: &Search{
				Query: "C-WWFCCH41R123",
				Type:  1,
				Result: map[string]interface{}{
					"Activation key": "C-WWFCCH41R123",
					"Used":           "Yes",
					"Email":          "test@email.com",
					"LPA":            testLPA.SiriusUID,
				},
				Errors: nil,
			},
		},
		{
			name: "Normal email query",
			args: args{
				accountService: &mockAccountService{},
				lpaService:     &mockLPAService{},
				q:              "query=test@email.com",
			},
			expected: &Search{Query: "test@email.com", Type: 0, Result: &data.ActorUser{LPAs: []*data.LPA{}}, Errors: nil},
		},
		{
			name: "Test validation failure",
			args: args{
				accountService: &mockAccountService{},
				lpaService:     &mockLPAService{GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) { return testLPA, nil }},
				q:              "query=C-A",
			},
			expected: &Search{Query: "C-A", Type: 0, Result: nil, Errors: validation.Errors{"Query": errors.New("Enter an email address or activation code")}},
		},
		{
			name: "Test search with no query",
			args: args{
				accountService: &mockAccountService{},
				lpaService:     &mockLPAService{GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) { return testLPA, nil }},
				q:              "",
			},
			expected: &Search{Query: "", Type: 0, Result: nil, Errors: validation.Errors{"Query": errors.New("Enter a search query")}},
		},
	}

	for _, tt := range tests {
		tt := tt
		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			ts := &mockTemplateWriterService{RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, s string, i interface{}) error {
				if !reflect.DeepEqual(i, tt.expected) {
					t.Errorf("SearchHandler() = %v, want %v", i, tt.expected)
					got := reflect.ValueOf(i)
					t.Errorf("got %v ", got)
				}
				return nil
			}}

			server := NewSearchServer(tt.args.accountService, tt.args.lpaService, ts)
			reader := strings.NewReader(tt.args.q)
			var req *http.Request

			if tt.args.q != "" {
				req, _ = http.NewRequest("POST", "/my_url", reader)
			} else {
				req, _ = http.NewRequest("POST", "/my_url", nil)
			}
			req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
			w := httptest.NewRecorder()

			server.SearchHandler(w, req)

		})

	}
}

func Test_TemplateErrorPanic(t *testing.T) {
	t.Parallel()

	testLPA := &data.LPA{
		SiriusUID: "700000000123",
		Added:     "Date Added",
		UserID:    "TestID",
	}

	t.Run("Template error ends in panic", func(t *testing.T) {
		t.Parallel()

		ts := &mockTemplateWriterService{
			RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, s string, i interface{}) error {
				return errors.New("I have errored")
			},
		}

		server := NewSearchServer(
			&mockAccountService{
				GetEmailByUserIDFunc: func(ctx context.Context, s string) (string, error) { return "test@email.com", nil },
			},
			&mockLPAService{
				GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) { return testLPA, nil },
			},
			ts,
		)
		reader := strings.NewReader("query=C-WWFCCH41R123")
		var req *http.Request

		req, _ = http.NewRequest("POST", "/my_url", reader)

		req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
		w := httptest.NewRecorder()

		server.SearchHandler(w, req)

		if w.Code != http.StatusOK {
			t.Errorf("Wrong status code in TestTemplateErrorPanic expected %v got %v", http.StatusOK, w.Code)
		}

	})
}
