package data_test

import (
	"context"
	"errors"
	"fmt"
	"reflect"
	"testing"

	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"

	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/stretchr/testify/assert"
)

type mockDynamoDBClient struct {
	QueryFunc func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
}

func (m *mockDynamoDBClient) Query(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
	return m.QueryFunc(ctx, params, optFns...)
}

func TestGetLpasByUserID(t *testing.T) {
	t.Parallel()

	tests := []struct {
		name      string
		userID    string
		queryFunc func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
		want      *LPA
		wantErr   assert.ErrorAssertionFunc
	}{
		{
			name:   "find lpas for a user id",
			userID: "1",
			queryFunc: func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
				return &dynamodb.QueryOutput{
					Count: 1,
					Items: []map[string]types.AttributeValue{
						{"siriusUid": &types.AttributeValueMemberS{Value: "700000001"}},
					},
				}, nil
			},
			want: &LPA{
				UserID: "1",
			},
			wantErr: assert.NoError,
		},
		{
			name:   "error whilst searching for userId",
			userID: "1",
			queryFunc: func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
				return &dynamodb.QueryOutput{
					Count: 0,
				}, errors.New("some error")
			},
			want:    nil,
			wantErr: assert.Error,
		},
	}

	for _, tt := range tests {
		tt := tt

		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			dynamodbConnection := DynamoConnection{
				Client: &mockDynamoDBClent{
					QueryFunc: tt.queryFunc,
				},
				Prefix: "",
			}

			client := NewLPAService(dynamodbConnection)

			lpas, err := client.GetLpasByUserID(context.Background(), tt.userID)

			if tt.wantErr(t, err, fmt.Sprintf("GetLpasByUserID(%v)", tt.userID)) {
				return
			}
			assert.EqualValues(t, tt.want, lpas)
		})
	}
}

func TestGetLPAByActivationCode(t *testing.T) {
	t.Parallel()

	tests := []struct {
		name           string
		activationCode string
		queryFunc      func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
		want           *LPA
		wantErr        assert.ErrorAssertionFunc
	}{
		{
			name:           "find lpas for a user id",
			activationCode: "ABCD1234WXYZ",
			queryFunc: func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
				return &dynamodb.QueryOutput{
					Count: 1,
					Items: []map[string]types.AttributeValue{
						{"siriusUid": &types.AttributeValueMemberS{Value: "700000001"}},
					},
				}, nil
			},
			want: &LPA{
				SiriusUID: "700000001",
			},
			wantErr: assert.NoError,
		},
		{
			name:           "error while searching for activationCode",
			activationCode: "ABCD1234WXYZ",
			queryFunc: func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
				return &dynamodb.QueryOutput{
					Count: 0,
				}, errors.New("some error")
			},
			want:    nil,
			wantErr: assert.Error,
		},
	}

	for _, tt := range tests {
		tt := tt

		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			dynamodbConnection := DynamoConnection{
				Client: &mockDynamoDBClent{
					QueryFunc: tt.queryFunc,
				},
				Prefix: "",
			}

			client := NewLPAService(dynamodbConnection)

			lpas, err := client.GetLPAByActivationCode(context.Background(), tt.activationCode)

			if tt.wantErr(t, err, fmt.Sprintf("GetLPAByActivationCode(%v)", tt.activationCode)) {
				return
			}
			assert.EqualValues(t, tt.want, lpas)
		})
	}
}

func Test_lpaService_GetLPARecordBySiriusID(t *testing.T) {
	type args struct {
		ctx       context.Context
		lpaNumber string
	}
	tests := []struct {
		name      string
		args      args
		queryFunc func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
		wantLpas  []*LPA
		wantErr   bool
	}{
		{
			name: "Get Records by Sirius ID",
			args: args{
				ctx:       context.Background(),
				lpaNumber: "700000000138",
			},
			queryFunc: func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
				return &dynamodb.QueryOutput{
					Count: 1,
					Items: []map[string]types.AttributeValue{
						{
							"SiriusUid": &types.AttributeValueMemberS{Value: "700000000138"},
							"Added":     &types.AttributeValueMemberS{Value: "2020-08-20T14:37:49.522828Z"},
							"UserId":    &types.AttributeValueMemberS{Value: "bf9e7e77-f283-49c6-a79c-65d5d309ef77"},
						},
						{
							"SiriusUid": &types.AttributeValueMemberS{Value: "700000000138"},
							"Added":     &types.AttributeValueMemberS{Value: "2020-08-20T14:37:49.522828Z"},
							"UserId":    &types.AttributeValueMemberS{Value: "9123b6f9-0cbe-4a2e-a204-d723c80de6dc"},
						},
					},
				}, nil
			},
			wantLpas: []*LPA{
				{
					SiriusUID: "700000000138",
					Added:     "2020-08-20T14:37:49.522828Z",
					UserID:    "bf9e7e77-f283-49c6-a79c-65d5d309ef77",
				},
				{
					SiriusUID: "700000000138",
					Added:     "2020-08-20T14:37:49.522828Z",
					UserID:    "9123b6f9-0cbe-4a2e-a204-d723c80de6dc",
				},
			},
		},
		{
			name: "Error getting lpa records",
			args: args{
				ctx:       context.Background(),
				lpaNumber: "700000000138",
			},
			queryFunc: func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
				return &dynamodb.QueryOutput{Count: 0}, errors.New("Some Error")
			},
			wantLpas: nil,
			wantErr:  true,
		},
	}

	for _, tt := range tests {

		dynamodbConnection := DynamoConnection{
			Client: &mockDynamoDBClent{
				QueryFunc: tt.queryFunc,
			},
			Prefix: "",
		}

		t.Run(tt.name, func(t *testing.T) {
			l := NewLPAService(dynamodbConnection)
			gotLpas, err := l.GetLpaRecordBySiriusID(tt.args.ctx, tt.args.lpaNumber)
			if (err != nil) != tt.wantErr {
				t.Errorf("lpaService.GetUsersByLPAID() error = %v, wantErr %v", err, tt.wantErr)
				return
			}
			if !reflect.DeepEqual(gotLpas, tt.wantLpas) {
				t.Errorf("lpaService.GetUsersByLPAID() = %v, want %v", gotLpas, tt.wantLpas)
			}
		})
	}
}
