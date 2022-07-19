package data

import (
	"context"

	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/rs/zerolog/log"
)

var prefix string

func NewDynamoConnection(region string, endpoint string, tablePrefix string) *dynamodb.Client {
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

	return svc
}

func PrefixedTableName(name string) string {
	return prefix + name
}
