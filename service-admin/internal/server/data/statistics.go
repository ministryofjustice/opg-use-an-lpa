package data

import (
	"context"
	"errors"

	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/rs/zerolog/log"
)

type StatisticsService struct {
	db DynamoConnection
}

const (
	StatsTableName = "Stats"
)

var ErrMetricsNotFound = errors.New("metrics not found")
var ErrMetricsPerTimePeriodNotFound = errors.New("metrics per month not found")

func NewStatisticsService(db DynamoConnection) *StatisticsService {
	return &StatisticsService{db: db}
}

func (s *StatisticsService) GetAllMetrics(ctx context.Context, list []string) (metricValues map[string]map[string]float64, err error) {
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
					{
						"TimePeriod": &types.AttributeValueMemberS{Value: list[4]},
					},
				},
			},
		},
	})

	if err != nil {
		log.Error().Err(err).Msg("batch load of added lpas failed")
	}

	if len(result.Responses) > 0 {
		metricValues := make(map[string]map[string]float64)

		for _, table := range result.Responses {
			for _, item := range table {
				currentMonthValues := make(map[string]float64)

				for metricName, metricValue := range item {
					if metricName != "TimePeriod" {
						var unMarshalledValue float64

						err = attributevalue.Unmarshal(metricValue, &unMarshalledValue)
						if err != nil {
							log.Error().Err(err).Msg("unable to convert dynamo result into metricValue")
						}

						currentMonthValues[metricName] = unMarshalledValue
					}
				}

				var unMarshalledValue string

				err = attributevalue.Unmarshal(item["TimePeriod"], &unMarshalledValue)
				if err != nil {
					log.Error().Err(err).Msg("unable to convert dynamo result TimePeriod")
				}

				metricValues[unMarshalledValue] = currentMonthValues
			}
		}

		for key, value := range metricValues[list[0]] {
			metricValues["Total"][key] = metricValues["Total"][key] + value
		}

		return metricValues, nil
	}

	return nil, ErrMetricsNotFound
}
