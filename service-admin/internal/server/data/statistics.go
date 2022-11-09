package data

import (
	"context"
	"errors"
	"fmt"

	//"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"

	//	"github.com/aws/aws-sdk-go/service/dynamodb/dynamodbattribute"

	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
)

type statisticsService struct {
	db DynamoConnection
}

type LpasAddedResult struct {
	Metric    string
	Total     string
	ThisMonth  int
	LastMonth  int
	MothBefore int
}

type MetricPerTimePeriod struct {
	TimePeriod      string
	lpas_added      int
}

type LpasAdded struct {
  metricFor     int

	AddedLPAsPerTimePeriod []*MetricPerTimePeriod
}

const (
	StatsTableName  = "Stats"
)

var ErrTotalLpasAddedNotFound = errors.New("total Lpas added not found")
var ErrLpasAddedPerTimePeriodNotFound = errors.New("lpas added per month not found")

func NewStatisticsService(db DynamoConnection) *statisticsService {
	return &statisticsService{db: db}
}
//lpa *LpasAdded

func (s *statisticsService) GetAddedLpas(ctx context.Context, list []string) (lpa []*MetricPerTimePeriod, err error) {
	result, err := s.db.Client.BatchGetItem(ctx, &dynamodb.BatchGetItemInput{
		RequestItems: map[string]types.KeysAndAttributes{
			s.db.prefixedTableName(StatsTableName): {
				Keys: []map[string]types.AttributeValue{
					{
						"TimePeriod": &types.AttributeValueMemberS{Value: list[0]},
					},
					{
						"TimePeriod": &types.AttributeValueMemberS{Value: list[1]},
					},
					{
						"TimePeriod": &types.AttributeValueMemberS{Value: list[2]},
					},
					{
						"TimePeriod": &types.AttributeValueMemberS{Value: list[3]},
					},
			},
		},
	},
	})

	if err != nil {
    panic(fmt.Errorf("batch load of added lpas failed, err: %w", err))
	}

	//lpasAddedValues := []MetricPerTimePeriod{} 


	metricValues := []MetricPerTimePeriod{}
	for _, table := range result.Responses {
		for _, item :=range table {
			
			//lpasAddedValue := MetricPerTimePeriod{}
		  timePeriod := MetricPerTimePeriod{}
			
			//err = attributevalue.UnmarshalMap(item, &lpasAddedValue)
			
			err = attributevalue.UnmarshalMap(item, &timePeriod)
			
			if err != nil {
				panic(fmt.Errorf("failed to unmarshall addedlpas from dynamodb response, err: %w", err))
			}

			metricValues = append(metricValues, timePeriod)

		}

	}
	//results := []LpasAdded{}
	
	if err == nil {
		timePeriod1 := result.Responses[s.db.prefixedTableName(StatsTableName)][0]
		timePeriod2 := result.Responses[s.db.prefixedTableName(StatsTableName)][1]
		timePeriod3 := result.Responses[s.db.prefixedTableName(StatsTableName)][2]
		total := result.Responses[s.db.prefixedTableName(StatsTableName)][3]
	
		fmt.Println(timePeriod1)
		fmt.Println(timePeriod2)
		fmt.Println(timePeriod3)
		fmt.Println(total)
		return nil, nil
	}

	fmt.Println(result.Responses)
	return nil, ErrTotalLpasAddedNotFound
}