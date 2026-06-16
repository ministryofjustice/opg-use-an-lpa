package main

import (
	"cmp"
	"context"
	"errors"
	"os"
	"slices"
	"strings"
	"testing"

	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/stretchr/testify/require"
)

func setup(ctx context.Context, tableName string) (*dynamodb.Client, error) {
	cfg, err := config.LoadDefaultConfig(ctx)
	if err != nil {
		return nil, err
	}

	cfg.BaseEndpoint = new(cmp.Or(os.Getenv("LOCAL_URL"), "http://localhost:4566"))
	cfg.Region = "eu-west-1"

	dynamoClient := dynamodb.NewFromConfig(cfg)

	if _, err := dynamoClient.DeleteTable(ctx, &dynamodb.DeleteTableInput{
		TableName: new(tableName),
	}); err != nil {
		var exception *types.ResourceNotFoundException
		if !errors.As(err, &exception) {
			return nil, err
		}
	}

	if _, err := dynamoClient.CreateTable(ctx, &dynamodb.CreateTableInput{
		TableName: new(tableName),
		AttributeDefinitions: []types.AttributeDefinition{
			{AttributeName: new("Id"), AttributeType: types.ScalarAttributeTypeS},
		},
		KeySchema: []types.KeySchemaElement{
			{AttributeName: new("Id"), KeyType: types.KeyTypeHash},
		},
		ProvisionedThroughput: &types.ProvisionedThroughput{
			ReadCapacityUnits:  new(int64(5)),
			WriteCapacityUnits: new(int64(5)),
		},
	}); err != nil {
		return nil, err
	}

	return dynamoClient, nil
}

func TestRun(t *testing.T) {
	var (
		ctx       = context.Background()
		tableName = "my-test-table"
	)

	dynamoClient, err := setup(ctx, tableName)
	require.NoError(t, err)

	_, err = dynamoClient.BatchWriteItem(ctx, &dynamodb.BatchWriteItemInput{
		RequestItems: map[string][]types.WriteRequest{
			tableName: {
				{
					PutRequest: &types.PutRequest{
						Item: map[string]types.AttributeValue{
							"Id":                  &types.AttributeValueMemberS{Value: "all-fields"},
							"ActivationToken":     &types.AttributeValueMemberS{Value: "sure"},
							"Comment":             &types.AttributeValueMemberS{Value: "what"},
							"CreatedAt":           &types.AttributeValueMemberS{Value: "2020-01-02"},
							"Email":               &types.AttributeValueMemberS{Value: "hey@example.com"},
							"EmailResetExpiry":    &types.AttributeValueMemberN{Value: "133"},
							"EmailResetToken":     &types.AttributeValueMemberS{Value: "__hefrhjjkfew="},
							"ExpiresTTL":          &types.AttributeValueMemberS{Value: "345"},
							"Identity":            &types.AttributeValueMemberS{Value: "urn:blah"},
							"LastLogin":           &types.AttributeValueMemberS{Value: "x-y-z"},
							"NeedsReset":          &types.AttributeValueMemberN{Value: "45343"},
							"NewEmail":            &types.AttributeValueMemberS{Value: "hey2@example.com"},
							"Password":            &types.AttributeValueMemberS{Value: "$5$rounds=fejhnwefjkf"},
							"PasswordResetExpiry": &types.AttributeValueMemberN{Value: "123"},
							"PasswordResetToken":  &types.AttributeValueMemberS{Value: "__efhwefk__"},
						},
					},
				},
				{
					PutRequest: &types.PutRequest{
						Item: map[string]types.AttributeValue{
							"Id":                  &types.AttributeValueMemberS{Value: "just-old-fields"},
							"CreatedAt":           &types.AttributeValueMemberS{Value: "2020-01-02"},
							"Email":               &types.AttributeValueMemberS{Value: "hey@example.com"},
							"EmailResetExpiry":    &types.AttributeValueMemberN{Value: "133"},
							"EmailResetToken":     &types.AttributeValueMemberS{Value: "__hefrhjjkfew="},
							"ExpiresTTL":          &types.AttributeValueMemberS{Value: "345"},
							"LastLogin":           &types.AttributeValueMemberS{Value: "x-y-z"},
							"NeedsReset":          &types.AttributeValueMemberN{Value: "45343"},
							"NewEmail":            &types.AttributeValueMemberS{Value: "hey2@example.com"},
							"Password":            &types.AttributeValueMemberS{Value: "$5$rounds=fejhnwefjkf"},
							"PasswordResetExpiry": &types.AttributeValueMemberN{Value: "123"},
							"PasswordResetToken":  &types.AttributeValueMemberS{Value: "__efhwefk__"},
						},
					},
				},
				{
					PutRequest: &types.PutRequest{
						Item: map[string]types.AttributeValue{
							"Id":        &types.AttributeValueMemberS{Value: "just-new-fields"},
							"CreatedAt": &types.AttributeValueMemberS{Value: "2020-01-02"},
							"Email":     &types.AttributeValueMemberS{Value: "hey@example.com"},
							"Identity":  &types.AttributeValueMemberS{Value: "urn:blah"},
							"LastLogin": &types.AttributeValueMemberS{Value: "x-y-z"},
						},
					},
				},
				{
					PutRequest: &types.PutRequest{
						Item: map[string]types.AttributeValue{
							"Id":       &types.AttributeValueMemberS{Value: "IDENTITY#a"},
							"Anything": &types.AttributeValueMemberS{Value: "keep me"},
						},
					},
				},
				{
					PutRequest: &types.PutRequest{
						Item: map[string]types.AttributeValue{
							"Id":      &types.AttributeValueMemberS{Value: "EMAIL#a"},
							"Ignored": &types.AttributeValueMemberS{Value: "please"},
						},
					},
				},
			},
		},
	})

	require.NoError(t, err)

	err = updateRecords(ctx, dynamoClient, tableName, true)
	require.NoError(t, err)

	expected := []map[string]types.AttributeValue{
		{
			"Id":        &types.AttributeValueMemberS{Value: "all-fields"},
			"Comment":   &types.AttributeValueMemberS{Value: "what"},
			"CreatedAt": &types.AttributeValueMemberS{Value: "2020-01-02"},
			"Email":     &types.AttributeValueMemberS{Value: "hey@example.com"},
			"Identity":  &types.AttributeValueMemberS{Value: "urn:blah"},
			"LastLogin": &types.AttributeValueMemberS{Value: "x-y-z"},
		},
		{
			"Id":        &types.AttributeValueMemberS{Value: "just-new-fields"},
			"CreatedAt": &types.AttributeValueMemberS{Value: "2020-01-02"},
			"Email":     &types.AttributeValueMemberS{Value: "hey@example.com"},
			"Identity":  &types.AttributeValueMemberS{Value: "urn:blah"},
			"LastLogin": &types.AttributeValueMemberS{Value: "x-y-z"},
		},
		{
			"Id":        &types.AttributeValueMemberS{Value: "just-old-fields"},
			"CreatedAt": &types.AttributeValueMemberS{Value: "2020-01-02"},
			"Email":     &types.AttributeValueMemberS{Value: "hey@example.com"},
			"LastLogin": &types.AttributeValueMemberS{Value: "x-y-z"},
		},
		{
			"Id":      &types.AttributeValueMemberS{Value: "EMAIL#a"},
			"Ignored": &types.AttributeValueMemberS{Value: "please"},
		},
		{
			"Id":       &types.AttributeValueMemberS{Value: "IDENTITY#a"},
			"Anything": &types.AttributeValueMemberS{Value: "keep me"},
		},
	}

	results, err := dynamoClient.Scan(ctx, &dynamodb.ScanInput{
		TableName: new(tableName),
	})
	require.NoError(t, err)

	slices.SortFunc(expected, compareID)
	slices.SortFunc(results.Items, compareID)

	require.Equal(t, expected, results.Items)
}

func compareID(a, b map[string]types.AttributeValue) int {
	var idA string
	attributevalue.Unmarshal(a["Id"], &idA)

	var idB string
	attributevalue.Unmarshal(b["Id"], &idB)

	return strings.Compare(idA, idB)
}
