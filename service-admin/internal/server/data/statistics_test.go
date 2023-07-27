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

type mockDynamoDBBatchClient struct {
	QueryFunc        func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
	GetItemFunc      func(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error)
	BatchGetItemFunc func(ctx context.Context, params *dynamodb.BatchGetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.BatchGetItemOutput, error)
}

func (m *mockDynamoDBBatchClient) Query(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
	return m.QueryFunc(ctx, params, optFns...)
}

func (m *mockDynamoDBBatchClient) GetItem(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error) {
	return m.GetItemFunc(ctx, params, optFns...)
}

func (m *mockDynamoDBBatchClient) BatchGetItem(ctx context.Context, params *dynamodb.BatchGetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.BatchGetItemOutput, error) {
	return m.BatchGetItemFunc(ctx, params, optFns...)
}

func TestGetAllMetrics(t *testing.T) {
	t.Parallel()

	tests := []struct {
		name             string
		list             []string
		batchGetItemFunc func(ctx context.Context, params *dynamodb.BatchGetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.BatchGetItemOutput, error)
		want             map[string]map[string]float64
		wantErr          assert.ErrorAssertionFunc
		errorExpected    bool
	}{
		{
			name: "find metric values for last 3 months",
			list: []string{"2022-11", "2022-10", "2022-09", "2022-08", "Total"},
			batchGetItemFunc: func(ctx context.Context, params *dynamodb.BatchGetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.BatchGetItemOutput, error) {
				return &dynamodb.BatchGetItemOutput{
					Responses: map[string][]map[string]types.AttributeValue{
						"Stats": {
							{
								"TimePeriod":            &types.AttributeValueMemberS{Value: "2022-11"},
								"lpas_added":            &types.AttributeValueMemberN{Value: "1"},
								"lpa_removed_evet":      &types.AttributeValueMemberN{Value: "1"},
								"account_created_event": &types.AttributeValueMemberN{Value: "5"},
							},
							{
								"TimePeriod":            &types.AttributeValueMemberS{Value: "2022-10"},
								"lpas_added":            &types.AttributeValueMemberN{Value: "4"},
								"lpa_removed_evet":      &types.AttributeValueMemberN{Value: "2"},
								"account_created_event": &types.AttributeValueMemberN{Value: "7"},
							},
							{
								"TimePeriod":            &types.AttributeValueMemberS{Value: "2022-09"},
								"lpas_added":            &types.AttributeValueMemberN{Value: "3"},
								"lpa_removed_evet":      &types.AttributeValueMemberN{Value: "1"},
								"account_created_event": &types.AttributeValueMemberN{Value: "4"},
							},
              {
								"TimePeriod":            &types.AttributeValueMemberS{Value: "2022-08"},
								"lpas_added":            &types.AttributeValueMemberN{Value: "1"},
								"lpa_removed_evet":      &types.AttributeValueMemberN{Value: "1"},
								"account_created_event": &types.AttributeValueMemberN{Value: "1"},
							},
							{
								"TimePeriod":            &types.AttributeValueMemberS{Value: "Total"},
								"lpas_added":            &types.AttributeValueMemberN{Value: "11"},
								"lpa_removed_evet":      &types.AttributeValueMemberN{Value: "11"},
								"account_created_event": &types.AttributeValueMemberN{Value: "10"},
							},
						},
					},
				}, nil
			},
			want: map[string]map[string]float64{
				"2022-11": {
					"lpas_added":            1,
					"lpa_removed_evet":      1,
					"account_created_event": 5,
				},
				"2022-10": {
					"lpas_added":            4,
					"lpa_removed_evet":      2,
					"account_created_event": 7,
				},
				"2022-09": {
					"lpas_added":            3,
					"lpa_removed_evet":      1,
					"account_created_event": 4,
				},
        "2022-08": {
					"lpas_added":            1,
					"lpa_removed_evet":      1,
					"account_created_event": 1,
				},
				"Total": {
					"lpas_added":            12,
					"lpa_removed_evet":      12,
					"account_created_event": 15,
				},
			},
			wantErr: assert.NoError, errorExpected: false,
		},
		{
			name: "doesn't find a metric for time period",
			list: []string{"2022-11", "2022-10", "2022-09", "2022-08", "Total"},
			batchGetItemFunc: func(ctx context.Context, params *dynamodb.BatchGetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.BatchGetItemOutput, error) {
				return &dynamodb.BatchGetItemOutput{}, nil
			},
			want:    nil,
			wantErr: assert.Error,
		},
		{
			name: "error whilst searching for metrics",
			list: []string{"2022-11", "2022-10", "2022-09", "2022-08", "Total"},
			batchGetItemFunc: func(ctx context.Context, params *dynamodb.BatchGetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.BatchGetItemOutput, error) {
				return &dynamodb.BatchGetItemOutput{}, errors.New("some error")
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
				Client: &mockDynamoDBBatchClient{
					BatchGetItemFunc: tt.batchGetItemFunc,
				},
				Prefix: "",
			}

			client := NewStatisticsService(dynamodbConnection)

			metricValues, err := client.GetAllMetrics(context.Background(), tt.list)

			tt.wantErr(t, err, fmt.Sprintf("GetAllMetrics(%v)", tt.list))

			if !tt.errorExpected {
				assert.EqualValues(t, tt.want, metricValues)
			}
		})
	}
}
