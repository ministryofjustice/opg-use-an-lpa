package data

import (
	"os"

	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/aws/session"
	"github.com/aws/aws-sdk-go/service/dynamodb"
	"github.com/aws/aws-sdk-go/service/dynamodb/dynamodbiface"
	"github.com/rs/zerolog/log"
)

func NewDynamoConnection() dynamodbiface.DynamoDBAPI {
	reg := os.Getenv("AWS_REGION")
	if reg == "" {
		reg = "eu-west-1"
	}

	conf := aws.NewConfig().WithRegion(reg)

	if ep := os.Getenv("AWS_DYNAMODB_ENDPOINT"); ep != "" {
		conf = conf.WithEndpoint(ep)
	}

	session, err := session.NewSession(conf)
	if err != nil {
		log.Panic().Err(err).Msg("unable to create AWS session")
	}

	svc := dynamodb.New(session)

	return dynamodbiface.DynamoDBAPI(svc)
}
