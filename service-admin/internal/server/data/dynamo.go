package data

import (
	"context"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
)

type DynamoDBClient interface {
	Query(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
	GetItem(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error)
	BatchGetItem(ctx context.Context, params *dynamodb.BatchGetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.BatchGetItemOutput, error)
}

type DynamoConnection struct {
	Prefix string
	Client DynamoDBClient
}

func NewDynamoConnection(conf aws.Config, endpoint string, tablePrefix string) *DynamoConnection {
	prefix := ""
	if tablePrefix != "" {
		prefix = tablePrefix + "-"
	}

	svc := dynamodb.NewFromConfig(conf, func(o *dynamodb.Options) {
		if endpoint != "" {
			o.BaseEndpoint = &endpoint
		}
	})

	return &DynamoConnection{Prefix: prefix, Client: svc}
}

func (dc *DynamoConnection) prefixedTableName(name string) string {
	return dc.Prefix + name
}

