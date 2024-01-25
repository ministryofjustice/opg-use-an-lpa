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

type mockActivationKeyService struct {
	GetActivationKeyFromCodesFunc func(context.Context, string) (*[]data.ActivationKey, error)
}

func (m *mockActivationKeyService) GetActivationKeyFromCodes(ctx context.Context, key string) (*[]data.ActivationKey, error) {
	if m.GetActivationKeyFromCodesFunc != nil {
		return m.GetActivationKeyFromCodesFunc(ctx, key)
	}

	return nil, errors.New("NOT FOUND")
}

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
	GetLPARecordByLPAIDFunc    func(context.Context, string) ([]*data.LPA, error)
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

func (m *mockLPAService) GetLpaRecordBySiriusID(ctx context.Context, lpaID string) (userIDs []*data.LPA, err error) {
	if m.GetLPARecordByLPAIDFunc != nil {
		return m.GetLPARecordByLPAIDFunc(ctx, lpaID)
	}

	return []*data.LPA{}, nil
}

func Test_SearchByEmail(t *testing.T) {
	t.Parallel()

	testLPA := &data.LPA{
		SiriusUID: "700000000123",
		Added:     "Date Added",
		UserID:    "TestID",
	}

	type args struct {
		ctx       context.Context
		queryType QueryType
		q         string
	}

	tests := []struct {
		name                 string
		args                 args
		templateService      TemplateWriterService
		accountService       AccountService
		lpaService           LPAService
		activationKeyService data.ActivationKeyService
		want                 interface{}
	}{
		{
			name: "Test standard email query",
			args: args{
				ctx:       context.TODO(),
				queryType: 0, //Email query
				q:         "test@email.com",
			},
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
					t.FailNow()
					return nil, nil
				},
			},
			lpaService: &mockLPAService{
				GetLPAsByUserIDFunc: func(ctx context.Context, s string) ([]*data.LPA, error) {
					return []*data.LPA{testLPA}, nil
				},
			},
			activationKeyService: &mockActivationKeyService{},
			want:                 &data.ActorUser{ID: "TestID", Email: "test@email.com", LastLogin: "TestTime", LPAs: []*data.LPA{testLPA}},
		},
		{
			name: "Test email query with error on account lookup",
			args: args{
				ctx:       context.TODO(),
				queryType: 0, //Email query
				q:         "test@email.com",
			},
			accountService: &mockAccountService{
				GetActorByUserEmailFunc: func(ctx context.Context, s string) (*data.ActorUser, error) {
					return nil, errors.New("this is an error")
				},
			},
			lpaService:           &mockLPAService{},
			activationKeyService: &mockActivationKeyService{},
			want:                 nil,
		},
		{
			name: "Test email query with error on LPA lookup",
			args: args{
				ctx:       context.TODO(),
				queryType: 0, //Email query
				q:         "test@email.com",
			},
			accountService: &mockAccountService{},
			lpaService: &mockLPAService{
				GetLPAsByUserIDFunc: func(ctx context.Context, s string) ([]*data.LPA, error) {
					return nil, errors.New("this is an error")
				},
			},
			activationKeyService: &mockActivationKeyService{},
			want:                 nil,
		},
		{
			name: "Test email query with not found error on LPA lookup returns empty result not nil",
			args: args{
				ctx:       context.TODO(),
				queryType: 0, //Email query
				q:         "test@email.com",
			},
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
			activationKeyService: &mockActivationKeyService{},
			want:                 &data.ActorUser{ID: "TestID", Email: "test@email.com", LastLogin: "TestTime", LPAs: nil},
		},
	}

	for _, tt := range tests {
		tt := tt
		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			s := NewSearchServer(tt.accountService, tt.lpaService, tt.templateService, tt.activationKeyService)

			if got := s.SearchByEmail(tt.args.ctx, tt.args.q); !reflect.DeepEqual(got, tt.want) {
				t.Errorf("SearchByEmail() = %v, want %v", got, tt.want)
			}
		})
	}
}

func Test_SearchByLPANumber(t *testing.T) {
	t.Parallel()

	testLPA := &data.LPA{
		SiriusUID:   "700000000123",
		Added:       "2020-08-19T15:22:32.838097Z",
		UserID:      "TestID",
		ActivatedOn: "2020-08-20T15:22:32.838097Z",
	}

	type args struct {
		ctx       context.Context
		queryType QueryType
		q         string
	}

	tests := []struct {
		name                 string
		args                 args
		templateService      TemplateWriterService
		accountService       AccountService
		lpaService           LPAService
		activationKeyService data.ActivationKeyService
		want                 interface{}
	}{
		{
			name: "Test standard lpa number query",
			args: args{
				ctx:       context.TODO(),
				queryType: 2, //LPA number query
				q:         "7000-0000-0000",
			},
			accountService: &mockAccountService{
				GetEmailByUserIDFunc: func(ctx context.Context, s string) (string, error) {
					if s == testLPA.UserID {
						return "Test@email.com", nil
					}
					t.Errorf("Wrong user id given got %s expected %s", s, testLPA.UserID)
					t.FailNow()
					return "", nil
				},
			},
			lpaService: &mockLPAService{
				GetLPARecordByLPAIDFunc: func(ctx context.Context, s string) ([]*data.LPA, error) {
					return []*data.LPA{testLPA}, nil
				},
			},
			activationKeyService: &mockActivationKeyService{},
			want: map[string]interface{}{
				"LPANumber": "7000-0000-0000",
				"AddedBy":   []AddedBy{{DateAdded: "2020-08-19T15:22:32.838097Z", Email: "Test@email.com", ActivatedOn: "2020-08-20T15:22:32.838097Z"}},
			},
		},
		{
			name: "Test lpa not found gives nil",
			args: args{
				ctx:       context.TODO(),
				queryType: 2, //LPA number query
				q:         "7000-0000-0000",
			},
			accountService: &mockAccountService{},
			lpaService: &mockLPAService{
				GetLPARecordByLPAIDFunc: func(ctx context.Context, s string) ([]*data.LPA, error) {
					return nil, errors.New("NOT FOUND")
				},
			},
			activationKeyService: &mockActivationKeyService{},
			want:                 nil,
		},
	}

	for _, tt := range tests {
		tt := tt
		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			s := NewSearchServer(tt.accountService, tt.lpaService, tt.templateService, tt.activationKeyService)

			if got := s.SearchByLPANumber(tt.args.ctx, tt.args.q); !reflect.DeepEqual(got, tt.want) {
				t.Errorf("SearchByEmail() = %v, want %v", got, tt.want)
			}
		})
	}
}

func Test_SearchByActivationCode(t *testing.T) {
	t.Parallel()

	testLPA := &data.LPA{
		SiriusUID: "700000000123",
		Added:     "Date Added",
		UserID:    "TestID",
	}

	type args struct {
		ctx       context.Context
		queryType QueryType
		q         string
	}

	tests := []struct {
		name                 string
		args                 args
		templateService      TemplateWriterService
		accountService       AccountService
		lpaService           LPAService
		activationKeyService data.ActivationKeyService
		want                 interface{}
	}{
		{
			name: "Test standard activation key query",
			args: args{
				ctx: context.TODO(),

				queryType: 1, //Key query
				q:         "WWFCCH41R123",
			},
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
			activationKeyService: &mockActivationKeyService{},
			want: &SearchResult{
				Query: "WWFCCH41R123",
				Used:  "Yes",
				Email: "test@email.com",
				LPA:   testLPA.SiriusUID,
			},
		},
		{
			name: "Test activation key query with email not found",
			args: args{
				ctx:       context.TODO(),
				queryType: 1, //Key query
				q:         "WWFCCH41R123",
			},
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
			activationKeyService: &mockActivationKeyService{},
			want: &SearchResult{
				Query: "WWFCCH41R123",
				Used:  "Yes",
				Email: "Not Found",
				LPA:   testLPA.SiriusUID,
			},
		},
		{
			name: "Test activation key query with error finding email result by a UID",
			args: args{
				ctx:       context.TODO(),
				queryType: 1, //Key query
				q:         "WWFCCH41R123",
			},
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
			activationKeyService: &mockActivationKeyService{},
			want:                 nil,
		},
		{
			name: "Test activation key query with error finding activation code",
			args: args{
				ctx:       context.TODO(),
				queryType: 1, //Key query
				q:         "WWFCCH41R123",
			},
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
			activationKeyService: &mockActivationKeyService{},
			want:                 nil,
		},
		{
			name: "Test standard Actvation key that does not have record in use db",
			args: args{
				ctx:       context.TODO(),
				queryType: 1, //Key query
				q:         "WWFCCH41R123",
			},
			accountService: &mockAccountService{},
			lpaService: &mockLPAService{
				GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) {
					return nil, errors.New("Error for test")
				},
			},
			activationKeyService: &mockActivationKeyService{
				GetActivationKeyFromCodesFunc: func(ctx context.Context, s string) (*[]data.ActivationKey, error) {
					return &[]data.ActivationKey{
						{
							Active:          false,
							Actor:           "700000000111",
							Code:            "WWFCCH41R123",
							Dob:             "20-06-1995",
							ExpiryDate:      1672568225,
							GeneratedDate:   "1-1-2022",
							LastUpdatedDate: "6-6-2022",
							Lpa:             "700000000123",
							StatusDetails:   "Revoked",
						},
					}, nil
				},
			},
			want: &SearchResult{
				Query: "WWFCCH41R123",
				Used:  "Yes",
				ActivationKey: &data.ActivationKey{
					Active:          false,
					Actor:           "700000000111",
					Code:            "WWFCCH41R123",
					Dob:             "20-06-1995",
					ExpiryDate:      1672568225,
					GeneratedDate:   "1-1-2022",
					LastUpdatedDate: "6-6-2022",
					Lpa:             "700000000123",
					StatusDetails:   "Revoked",
				},
				LPA: testLPA.SiriusUID,
			},
		},
		{
			name: "Test standard Actvation key that does not have record in use db and code not used",
			args: args{
				ctx:       context.TODO(),
				queryType: 1, //Key query
				q:         "WWFCCH41R123",
			},
			accountService: &mockAccountService{},
			lpaService: &mockLPAService{
				GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) {
					return nil, errors.New("Error for test")
				},
			},
			activationKeyService: &mockActivationKeyService{
				GetActivationKeyFromCodesFunc: func(ctx context.Context, s string) (*[]data.ActivationKey, error) {
					return &[]data.ActivationKey{
						{
							Active:          true,
							Actor:           "700000000111",
							Code:            "WWFCCH41R123",
							Dob:             "20-06-1995",
							ExpiryDate:      1672568225,
							GeneratedDate:   "1-1-2022",
							LastUpdatedDate: "6-6-2022",
							Lpa:             "700000000123",
							StatusDetails:   "Imported",
						},
					}, nil
				},
			},
			want: &SearchResult{
				Query: "WWFCCH41R123",
				Used:  "No",
				ActivationKey: &data.ActivationKey{
					Active:          true,
					Actor:           "700000000111",
					Code:            "WWFCCH41R123",
					Dob:             "20-06-1995",
					ExpiryDate:      1672568225,
					GeneratedDate:   "1-1-2022",
					LastUpdatedDate: "6-6-2022",
					Lpa:             "700000000123",
					StatusDetails:   "Imported",
				},
				LPA: testLPA.SiriusUID,
			},
		},
		{
			name: "Test standard activation key query with code request and email",
			args: args{
				ctx:       context.TODO(),
				queryType: 1, //Key query
				q:         "WWFCCH41R123",
			},
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
			activationKeyService: &mockActivationKeyService{
				GetActivationKeyFromCodesFunc: func(ctx context.Context, s string) (*[]data.ActivationKey, error) {
					return &[]data.ActivationKey{
						{
							Active:          false,
							Actor:           "700000000111",
							Code:            "WWFCCH41R123",
							Dob:             "20-06-1995",
							ExpiryDate:      1672568225,
							GeneratedDate:   "1-1-2022",
							LastUpdatedDate: "6-6-2022",
							Lpa:             "700000000138",
							StatusDetails:   "Revoked",
						},
					}, nil
				},
			},
			want: &SearchResult{
				Query: "WWFCCH41R123",
				Used:  "Yes",
				Email: "test@email.com",
				ActivationKey: &data.ActivationKey{
					Active:          false,
					Actor:           "700000000111",
					Code:            "WWFCCH41R123",
					Dob:             "20-06-1995",
					ExpiryDate:      1672568225,
					GeneratedDate:   "1-1-2022",
					LastUpdatedDate: "6-6-2022",
					Lpa:             "700000000138",
					StatusDetails:   "Revoked",
				},
				LPA: testLPA.SiriusUID,
			},
		},
	}

	for _, tt := range tests {
		tt := tt
		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			s := NewSearchServer(tt.accountService, tt.lpaService, tt.templateService, tt.activationKeyService)

			if got := s.SearchByActivationCode(tt.args.ctx, tt.args.q); !reflect.DeepEqual(got, tt.want) {
				t.Errorf("doSearch() = %v, want %v", got, tt.want)
			}
		})
	}
}

func Test_SearchHandler(t *testing.T) {
	t.Parallel()

	testLPA := &data.LPA{
		SiriusUID:   "700000000123",
		Added:       "2020-08-19T15:22:32.838097Z",
		UserID:      "TestID",
		ActivatedOn: "2020-08-20T15:22:32.838097Z",
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
				lpaService: &mockLPAService{GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) {
					if s == "WWFCCH41R123" {
						return testLPA, nil
					}
					t.Errorf("Activation key given %s was not the same as expected WWFCCH41R123", s)
					t.FailNow()
					return nil, nil
				}},

				q: "query=C-wWFCcH41R123",
			},
			expected: &Search{
				Query: "C-wWFCcH41R123",
				Type:  1,
				Result: &SearchResult{
					Query:         "WWFCCH41R123",
					ActivationKey: nil,
					Used:          "Yes",
					Email:         "test@email.com",
					LPA:           testLPA.SiriusUID,
				},
				Errors: nil,
				Path:   "/my_url",
			},
		},
		{
			name: "Normal email query",
			args: args{
				accountService: &mockAccountService{GetActorByUserEmailFunc: func(ctx context.Context, s string) (*data.ActorUser, error) {
					if s == "test@email.com" {
						return &data.ActorUser{ID: "700000000123", Email: "test@email.com", ActivationToken: "WWFCCH41R123", LPAs: []*data.LPA{}}, nil
					}
					t.Errorf("Wrong email given expected test@email.com, recieved %s", s)
					t.FailNow()
					return nil, nil
				}},
				lpaService: &mockLPAService{},
				q:          "query=test@email.com",
			},
			expected: &Search{
				Query: "test@email.com",
				Type:  0,
				Result: &data.ActorUser{
					ID:              "700000000123",
					Email:           "test@email.com",
					ActivationToken: "WWFCCH41R123",
					LPAs:            []*data.LPA{}},
				Errors: nil,
				Path:   "/my_url",
			},
		},
		{
			name: "Email query is case insensitive",
			args: args{
				accountService: &mockAccountService{GetActorByUserEmailFunc: func(ctx context.Context, s string) (*data.ActorUser, error) {
					if s == "test@email.com" {
						return &data.ActorUser{ID: "700000000123", Email: "test@email.com", ActivationToken: "WWFCCH41R123", LPAs: []*data.LPA{}}, nil
					}
					t.Errorf("Wrong email given expected test@email.com, recieved %s", s)
					t.FailNow()
					return nil, nil
				}},
				lpaService: &mockLPAService{},
				q:          "query=TEST@email.com",
			},
			expected: &Search{
				Query: "test@email.com",
				Type:  0,
				Result: &data.ActorUser{
					ID:              "700000000123",
					Email:           "test@email.com",
					ActivationToken: "WWFCCH41R123",
					LPAs:            []*data.LPA{},
				},
				Errors: nil,
				Path:   "/my_url",
			},
		},
		{
			name: "Normal LPA number query",
			args: args{
				accountService: &mockAccountService{
					GetEmailByUserIDFunc: func(ctx context.Context, s string) (string, error) { return "test@email.com", nil }},
				lpaService: &mockLPAService{
					GetLPARecordByLPAIDFunc: func(ctx context.Context, s string) ([]*data.LPA, error) {
						return []*data.LPA{testLPA}, nil
					},
				},
				q: "query=7000-0000-0000",
			},
			expected: &Search{
				Query: "7000-0000-0000",
				Type:  2,
				Result: map[string]interface{}{
					"LPANumber": "700000000000",
					"AddedBy": []AddedBy{
						{
							DateAdded:   "2020-08-19T15:22:32.838097Z",
							Email:       "test@email.com",
							ActivatedOn: "2020-08-20T15:22:32.838097Z",
						},
					},
				},
				Errors: nil,
				Path:   "/my_url",
			},
		},
		{
			name: "Test validation failure",
			args: args{
				accountService: &mockAccountService{},
				lpaService:     &mockLPAService{GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) { return testLPA, nil }},
				q:              "query=C-A",
			},
			expected: &Search{
				Query:  "C-A",
				Type:   0,
				Result: nil,
				Errors: validation.Errors{"Query": errors.New("Enter an email address or activation code")},
				Path:   "/my_url",
			},
		},
		{
			name: "Test search with no query",
			args: args{
				accountService: &mockAccountService{},
				lpaService:     &mockLPAService{GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) { return testLPA, nil }},
				q:              "",
			},
			expected: &Search{
				Query:  "",
				Type:   0,
				Result: nil,
				Errors: validation.Errors{"Query": errors.New("Enter a search query")},
				Path:   "/my_url",
			},
		},
		{
			name: "Test correct characters are removed",
			args: args{
				accountService: &mockAccountService{},
				lpaService:     &mockLPAService{GetLPAByActivationCodeFunc: func(ctx context.Context, s string) (*data.LPA, error) { return testLPA, nil }},
				q:              "query=C-WWFCCH41-R123",
			},
			expected: &Search{
				Query: "C-WWFCCH41-R123",
				Type:  1,
				Result: &SearchResult{
					Query:         "WWFCCH41R123",
					ActivationKey: nil,
					Used:          "Yes",
					Email:         "Not Found",
					LPA:           testLPA.SiriusUID,
				},
				Errors: nil,
				Path:   "/my_url",
			},
		},
	}

	for _, tt := range tests {
		tt := tt
		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			ts := &mockTemplateWriterService{RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, s string, i interface{}) error {
				if !reflect.DeepEqual(i, tt.expected) {
					t.Errorf("SearchHandler() = %v, want %v", i, tt.expected)
				}
				return nil
			}}

			server := NewSearchServer(tt.args.accountService, tt.args.lpaService, ts, &mockActivationKeyService{})
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
			&mockActivationKeyService{},
		)
		reader := strings.NewReader("query=C-WWFCCH41R123")
		var req *http.Request

		req, _ = http.NewRequest("POST", "/my_url", reader)

		req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
		w := httptest.NewRecorder()

		//recover panic
		defer func() { _ = recover() }()

		server.SearchHandler(w, req)

		t.Errorf("did not panic")

	})
}
