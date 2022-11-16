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
  QueryFunc   func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
	GetItemFunc func(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error)
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

func TestGetAddedLpas(t *testing.T) {
  t.Parallel()

  tests := []struct {
    name      string
    list      []string
    batchGetItemFunc func(ctx context.Context, params *dynamodb.BatchGetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.BatchGetItemOutput, error)
    want      *map[string]map[string]float64
    wantErr   assert.ErrorAssertionFunc
  }{
    {
      name:   "find metric values for last 3 months",
      list: []string {"2022-11", "2022-10", "2022-09", "Total"},
      batchGetItemFunc: func(ctx context.Context, params *dynamodb.BatchGetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.BatchGetItemOutput, error) {
        return &dynamodb.BatchGetItemOutput{
					Responses: map[string][]map[string]types.AttributeValue{		
						"Stats": {
              {
                "TimePeriod": &types.AttributeValueMemberS{Value: "2022-11"},
              },
            },
          },	
        }, nil 
      },
      want: &map[string]map[string]float64{
        "2022-11": {
          "lpas_added": 1,
          "lpa_removed_evet": 1,
          "account_created_event": 5,
        },
        "2022-10": {
          "lpas_added": 4,
          "lpa_removed_evet": 2,
          "account_created_event": 7,
        },
        "2022-09": {
          "lpas_added": 3,
          "lpa_removed_evet": 1,
          "account_created_event": 4,
        },
        "Total": {
          "lpas_added": 10,
          "lpa_removed_evet": 11,
          "account_created_event": 12,
        },
      },
      wantErr: assert.NoError,
    },
    {
      name:   "error whilst searching for metrics",
      list: []string {"2022-11", "2022-10", "2022-09", "Total"},
      batchGetItemFunc: func(ctx context.Context, params *dynamodb.BatchGetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.BatchGetItemOutput, error) {
        return &dynamodb.BatchGetItemOutput{
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
       Client: &mockDynamoDBBatchClient{
         BatchGetItemFunc: tt.batchGetItemFunc,
       },
       Prefix: "",
     }

     client := NewStatisticsService(dynamodbConnection)


     lpas, err := client.GetAllMetrics(context.Background(), tt.list)

     if tt.wantErr(t, err, fmt.Sprintf("GetAllMetrics(%v)", tt.list)) {
       return
     }
     assert.EqualValues(t, tt.want, lpas)
   })
  }
}

