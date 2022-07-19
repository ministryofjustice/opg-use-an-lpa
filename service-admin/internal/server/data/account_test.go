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

type mockDynamoDBClent struct {
	QueryFunc   func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
	GetItemFunc func(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error)
}

func (m *mockDynamoDBClent) Query(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
	return m.QueryFunc(ctx, params, optFns...)
}

func (m *mockDynamoDBClent) GetItem(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error) {
	return m.GetItemFunc(ctx, params, optFns...)
}

func TestGetActorUserByEmail(t *testing.T) {
	t.Parallel()

	tests := []struct {
		name      string
		email     string
		queryFunc func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
		want      *ActorUser
		wantErr   assert.ErrorAssertionFunc
	}{
		{
			name:  "finds a user by email",
			email: "test@example.com",
			queryFunc: func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
				return &dynamodb.QueryOutput{
					Count: 1,
					Items: []map[string]types.AttributeValue{
						{ "Email": &types.AttributeValueMemberS{ Value: "test@example.com" }, },
					},
				}, nil
			},
			want: &ActorUser{
				Email: "test@example.com",
			},
			wantErr: assert.NoError,
		},
		{
			name:  "doesn't find a user by email",
			email: "test@example.com",
			queryFunc: func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
				return &dynamodb.QueryOutput{
					Count: 0,
				}, nil
			},
			want:    nil,
			wantErr: assert.Error,
		},
		{
			name:  "error whilst searching for email",
			email: "test@example.com",
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

			mockDynamoDBClent := &mockDynamoDBClent{
				QueryFunc: tt.queryFunc,
			}

			client := NewAccountService(mockDynamoDBClent)

			actorUser, err := client.GetActorUserByEmail(context.Background(), tt.email)

			if tt.wantErr(t, err, fmt.Sprintf("GetActorUserByEmail(%v)", tt.email)) {
				return
			}
			assert.EqualValues(t, tt.want, actorUser)
		})
	}
}

func TestGetEmailByUserID(t *testing.T) {
	t.Parallel()

	tests := []struct {
		name      string
		userId     string
		getItemFunc func(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error)
		want     string
		wantErr   assert.ErrorAssertionFunc
	}{
		{
			name:  "Get email by userID",
			userId: "1",
			getItemFunc: func(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error) {
				return &dynamodb.GetItemOutput{		
					Item: map[string]types.AttributeValue{
						"userId": &types.AttributeValueMemberS{Value: "1"},
					},
				}, nil
			},
			want: "test@example.com",
			wantErr: assert.NoError,
		},
		{
			name:  "Error trying to get email by userID",
			userId: "1",
			getItemFunc: func(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error) {
				return nil,  errors.New("some error")
			},
			want: "",
			wantErr: assert.Error,
		},
	}
	
	for _, tt := range tests {
		tt := tt

		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			mockDynamoDBClent := &mockDynamoDBClent{
				GetItemFunc: tt.getItemFunc,
			}

			client := NewAccountService(mockDynamoDBClent)
			
			actorUser, err := client.GetEmailByUserID(context.Background(), tt.userId)

			if tt.wantErr(t, err, fmt.Sprintf("GetEmailByUserID(%v)", tt.userId)) {
				return
			}
			assert.EqualValues(t, tt.want, actorUser)
		})
	}
}
