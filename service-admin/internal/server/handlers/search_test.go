package handlers

import (
	"context"
	"reflect"
	"testing"

	validation "github.com/go-ozzo/ozzo-validation"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
)

func Test_search_Validate(t *testing.T) {
	type fields struct {
		Query  string
		Type   queryType
		Result interface{}
		Errors validation.Errors
	}
	tests := []struct {
		name    string
		fields  fields
		wantErr bool
	}{
		// TODO: Add test cases.
	}
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			s := &search{
				Query:  tt.fields.Query,
				Type:   tt.fields.Type,
				Result: tt.fields.Result,
				Errors: tt.fields.Errors,
			}
			if err := s.Validate(); (err != nil) != tt.wantErr {
				t.Errorf("search.Validate() error = %v, wantErr %v", err, tt.wantErr)
			}
		})
	}
}

func Test_search_checkEmailOrCode(t *testing.T) {
	type fields struct {
		Query  string
		Type   queryType
		Result interface{}
		Errors validation.Errors
	}
	type args struct {
		value interface{}
	}
	tests := []struct {
		name    string
		fields  fields
		args    args
		wantErr bool
	}{
		// TODO: Add test cases.
	}
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			s := &search{
				Query:  tt.fields.Query,
				Type:   tt.fields.Type,
				Result: tt.fields.Result,
				Errors: tt.fields.Errors,
			}
			if err := s.checkEmailOrCode(tt.args.value); (err != nil) != tt.wantErr {
				t.Errorf("search.checkEmailOrCode() error = %v, wantErr %v", err, tt.wantErr)
			}
		})
	}
}

type mockAccountService struct {
	ActorByUserEmail func(context.Context, string) (*data.ActorUser, error)
	EmailByUserID    func(context.Context, string) (string, error)
}

func (m *mockAccountService) GetActorUserByEmail(ctx context.Context, email string) (*data.ActorUser, error) {
	if m.ActorByUserEmail != nil {
		return m.ActorByUserEmail(ctx, email)
	}
	return &data.ActorUser{ID: ""}, nil
}

func (m *mockAccountService) GetEmailByUserID(ctx context.Context, userID string) (string, error) {
	if m.EmailByUserID != nil {
		return m.EmailByUserID(ctx, userID)
	}
	return "", nil
}

type mockLPAService struct {
	LPAsByUserID        func(context.Context, string) ([]*data.LPA, error)
	LPAByActivationCode func(context.Context, string) (*data.LPA, error)
}

func (m *mockLPAService) GetLpasByUserID(ctx context.Context, userID string) ([]*data.LPA, error) {
	if m.LPAsByUserID != nil {
		return m.LPAsByUserID(ctx, userID)
	}
	return []*data.LPA{}, nil
}

func (m *mockLPAService) GetLPAByActivationCode(ctx context.Context, code string) (*data.LPA, error) {
	if m.LPAByActivationCode != nil {
		return m.LPAByActivationCode(ctx, code)
	}
	return nil, nil
}

func Test_doSearch(t *testing.T) {

	testLPA := &data.LPA{
		SiriusUID: "700000000123",
		Added:     "Date Added",
		UserID:    "TestID",
	}

	type args struct {
		ctx            context.Context
		accountService AccountService
		lpaService     LPAService
		queryType      queryType
		q              string
	}
	tests := []struct {
		name string
		args args
		want interface{}
	}{
		{
			name: "Test standard email query",
			args: args{
				ctx: context.TODO(),
				accountService: &mockAccountService{
					ActorByUserEmail: func(ctx context.Context, s string) (*data.ActorUser, error) {
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
					LPAsByUserID: func(ctx context.Context, s string) ([]*data.LPA, error) {
						return []*data.LPA{testLPA}, nil
					},
				},
				queryType: 0, //Email query
				q:         "test@email.com",
			},
			want: &data.ActorUser{ID: "TestID", Email: "test@email.com", LastLogin: "TestTime", LPAs: []*data.LPA{testLPA}},
		},
	}
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			if got := doSearch(tt.args.ctx, tt.args.accountService, tt.args.lpaService, tt.args.queryType, tt.args.q); !reflect.DeepEqual(got, tt.want) {
				t.Errorf("doSearch() = %v, want %v", got, tt.want)
			}
		})
	}
}
