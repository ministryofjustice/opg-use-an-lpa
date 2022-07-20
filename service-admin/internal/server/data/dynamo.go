package data

import (
	"context"

	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/rs/zerolog/log"
)

type DynamoDBClient interface {
	Query(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
	GetItem(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error)
}

type DynamoConnection struct {
	Prefix string
	Client DynamoDBClient
}

func NewDynamoConnection(region string, endpoint string, tablePrefix string) *DynamoConnection {
	prefix := ""
	if tablePrefix != "" {
		prefix = tablePrefix + "-"
	}

	conf, err := config.LoadDefaultConfig(context.TODO(), func(lo *config.LoadOptions) error {
		if region != "" {
			lo.Region = region
		}

		return nil
	})
	if err != nil {
		log.Panic().Err(err).Msg("unable to create AWS client")
	}

	svc := dynamodb.NewFromConfig(conf, func(o *dynamodb.Options) {
		if endpoint != "" {
			o.EndpointResolver = dynamodb.EndpointResolverFromURL(endpoint)
		}
	})

	return &DynamoConnection{Prefix: prefix, Client: svc}
}

func (dc *DynamoConnection) prefixedTableName(name string) string {
	return dc.Prefix + name
}
