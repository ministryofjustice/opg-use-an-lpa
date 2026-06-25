package main

import (
	"context"
	"fmt"
	"log/slog"
	"math"
	"os"
	"slices"
	"time"

	"github.com/aws/aws-lambda-go/lambda"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/expression"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
)

var (
	dynamoClient *dynamodb.Client

	awsBaseURL = os.Getenv("AWS_BASE_URL")
	tableName  = os.Getenv("TABLE_NAME")
)

const (
	maxRetries     = 10
	initialBackoff = 100 * time.Millisecond
)

func main() {
	ctx := context.Background()

	logger := slog.New(slog.NewJSONHandler(os.Stdout, nil))
	slog.SetDefault(logger)

	cfg, err := config.LoadDefaultConfig(ctx)
	if err != nil {
		slog.Error(fmt.Sprintf("load config: %v", err))
		return
	}

	if awsBaseURL != "" {
		cfg.BaseEndpoint = new(awsBaseURL)
	}

	dynamoClient = dynamodb.NewFromConfig(cfg)

	lambda.Start(run)
}

type Event struct {
	Delete bool
}

func run(ctx context.Context, event Event) error {
	slog.Info("started run", slog.Bool("delete", event.Delete))

	if err := updateRecords(ctx, dynamoClient, tableName, event.Delete); err != nil {
		return fmt.Errorf("update records: %v", err)
	}

	slog.Info("finished run", slog.Bool("delete", event.Delete))
	return nil
}

func updateRecords(ctx context.Context, dynamoClient *dynamodb.Client, tableName string, doDelete bool) error {
	filterExpr := expression.Name("Id").BeginsWith("EMAIL#")

	expr, err := expression.NewBuilder().WithFilter(filterExpr).Build()
	if err != nil {
		return fmt.Errorf("build filter: %v", err)
	}

	paginator := dynamodb.NewScanPaginator(dynamoClient, &dynamodb.ScanInput{
		TableName:                 new(tableName),
		ExpressionAttributeNames:  expr.Names(),
		ExpressionAttributeValues: expr.Values(),
		FilterExpression:          expr.Filter(),
	})

	for paginator.HasMorePages() {
		response, err := paginator.NextPage(ctx)
		if err != nil {
			return fmt.Errorf("paginate: %v", err)
		}
		slog.Info("next page", slog.Int("count", len(response.Items)))

		writeRequests := makeWriteRequests(response.Items)
		if len(writeRequests) == 0 {
			continue
		}

		if doDelete {
			for chunk := range slices.Chunk(writeRequests, 25) {
				output, err := dynamoClient.BatchWriteItem(ctx, &dynamodb.BatchWriteItemInput{
					RequestItems: map[string][]types.WriteRequest{
						tableName: chunk,
					},
				})
				if err != nil {
					return fmt.Errorf("batch write item: %v", err)
				}

				retries := 0
				backoff := initialBackoff
				unprocessedItems := output.UnprocessedItems[tableName]

				for len(unprocessedItems) > 0 {
					retries++
					backoff = time.Duration(float64(backoff) * math.Pow(2, float64(retries)))
					time.Sleep(backoff)

					if retries > maxRetries {
						return fmt.Errorf("max retries: still have %d unprocessed items", len(unprocessedItems))
					} else {
						slog.Warn("reprocessing unprocessed items", slog.Any("count", len(unprocessedItems)))
					}

					output, err = dynamoClient.BatchWriteItem(ctx, &dynamodb.BatchWriteItemInput{
						RequestItems: map[string][]types.WriteRequest{
							tableName: unprocessedItems,
						},
					})
					if err != nil {
						return fmt.Errorf("retry %d of batch write item: %v", retries, err)
					}

					unprocessedItems = output.UnprocessedItems[tableName]
				}
			}

			slog.Info("deleted", slog.Int("count", len(writeRequests)))
		} else {
			for _, wr := range writeRequests {
				var id string
				_ = attributevalue.Unmarshal(wr.DeleteRequest.Key["Id"], &id)
				slog.Info("would delete", slog.Any("key", id))
			}
		}
	}

	return nil
}

type Item = map[string]types.AttributeValue

func makeWriteRequests(items []Item) []types.WriteRequest {
	var result []types.WriteRequest

	for _, item := range items {
		key := map[string]types.AttributeValue{
			"Id": item["Id"],
		}

		result = append(result, types.WriteRequest{
			DeleteRequest: &types.DeleteRequest{
				Key: key,
			},
		})
	}

	return result
}
