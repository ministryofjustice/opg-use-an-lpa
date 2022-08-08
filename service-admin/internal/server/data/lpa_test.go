package data_test

import (
	"context"
	"errors"
	"fmt"
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
		userId    string
		queryFunc func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
		want      *LPA
		wantErr   assert.ErrorAssertionFunc
	}{
		{
			name:   "find lpas for a user id",
			userId: "1",
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
			userId: "1",
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

			lpas, err := client.GetLpasByUserID(context.Background(), tt.userId)

			if tt.wantErr(t, err, fmt.Sprintf("GetLpasByUserID(%v)", tt.userId)) {
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
