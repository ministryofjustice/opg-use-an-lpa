package main

import (
	"cmp"
	"context"
	"errors"
	"fmt"
	"io/fs"
	"log/slog"
	"os"
	"path/filepath"
	"slices"
	"strings"
	"testing"
	"time"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/aws/aws-sdk-go-v2/service/s3"
	s3types "github.com/aws/aws-sdk-go-v2/service/s3/types"
)

func setup(ctx context.Context, tableName, bucketName string) (*s3.Client, *dynamodb.Client, error) {
	cfg, err := config.LoadDefaultConfig(ctx)
	if err != nil {
		return nil, nil, err
	}

	cfg.BaseEndpoint = aws.String(cmp.Or(os.Getenv("LOCAL_URL"), "http://localhost:4566"))
	cfg.Region = "eu-west-1"

	dynamoClient := dynamodb.NewFromConfig(cfg)

	if _, err := dynamoClient.DeleteTable(ctx, &dynamodb.DeleteTableInput{
		TableName: aws.String(tableName),
	}); err != nil {
		var exception *types.ResourceNotFoundException
		if !errors.As(err, &exception) {
			return nil, nil, err
		}
	}

	if _, err := dynamoClient.CreateTable(ctx, &dynamodb.CreateTableInput{
		TableName: aws.String(tableName),
		AttributeDefinitions: []types.AttributeDefinition{
			{AttributeName: aws.String("Id"), AttributeType: types.ScalarAttributeTypeS},
		},
		KeySchema: []types.KeySchemaElement{
			{AttributeName: aws.String("Id"), KeyType: types.KeyTypeHash},
		},
		ProvisionedThroughput: &types.ProvisionedThroughput{
			ReadCapacityUnits:  aws.Int64(5),
			WriteCapacityUnits: aws.Int64(5),
		},
	}); err != nil {
		return nil, nil, err
	}

	s3Client := s3.NewFromConfig(cfg, func(opts *s3.Options) {
		opts.UsePathStyle = true
	})

	objects, err := s3Client.ListObjects(ctx, &s3.ListObjectsInput{
		Bucket: aws.String(bucketName),
	})
	if err == nil {
		var identifiers []s3types.ObjectIdentifier
		for _, object := range objects.Contents {
			identifiers = append(identifiers, s3types.ObjectIdentifier{
				Key: object.Key,
			})
		}

		s3Client.DeleteObjects(ctx, &s3.DeleteObjectsInput{
			Bucket: aws.String(bucketName),
			Delete: &s3types.Delete{
				Objects: identifiers,
			},
		})
	}

	if _, err := s3Client.CreateBucket(ctx, &s3.CreateBucketInput{
		Bucket: aws.String(bucketName),
		CreateBucketConfiguration: &s3types.CreateBucketConfiguration{
			LocationConstraint: s3types.BucketLocationConstraint(s3types.BucketLocationConstraintEuWest1),
		},
	}); err != nil {
		var already *s3types.BucketAlreadyOwnedByYou
		if !errors.As(err, &already) {
			return nil, nil, err
		}
	} else {
		err = s3.NewBucketExistsWaiter(s3Client).
			Wait(ctx, &s3.HeadBucketInput{Bucket: aws.String(bucketName)}, time.Minute)
		if err != nil {
			return nil, nil, fmt.Errorf("waiting for bucket: %w", err)
		}
	}

	filepath.WalkDir("testdata", func(path string, d fs.DirEntry, err error) error {
		if err != nil {
			return err
		}
		if d.IsDir() {
			return nil
		}

		file, err := os.Open(path)
		if err != nil {
			return err
		}

		s3Client.PutObject(ctx, &s3.PutObjectInput{
			Bucket: aws.String(bucketName),
			Key:    aws.String(strings.Replace(path, "testdata/", "AWSDynamoDB/01765968403795-d1d972cf/", 1)),
			Body:   file,
		})

		return nil
	})

	return s3Client, dynamoClient, nil
}

func TestRun(t *testing.T) {
	var (
		ctx          = context.Background()
		tableName    = "my-test-table"
		bucketName   = "my-test-bucket"
		manifestPath = "AWSDynamoDB/01765968403795-d1d972cf/manifest-files.json"
	)

	s3Client, dynamoClient, err := setup(ctx, tableName, bucketName)
	if err != nil {
		t.Fatalf("setup: %v", err)
	}

	slog.SetDefault(slog.New(slog.NewTextHandler(os.Stdout, nil)))
	if err := processManifest(
		ctx,
		&BucketClient{client: s3Client, bucketName: bucketName},
		&TableClient{client: dynamoClient, tableName: tableName},
		manifestPath,
	); err != nil {
		t.Errorf("error returned: %v", err)
	}

	// should have the records in dynamo
	scanOutput, err := dynamoClient.Scan(ctx, &dynamodb.ScanInput{
		TableName: aws.String(tableName),
	})
	if err != nil {
		t.Errorf("error returned: %v", err)
	}

	var ids []string
	for _, item := range scanOutput.Items {
		var v map[string]any
		attributevalue.UnmarshalMap(item, &v)
		if len(v) != 1 {
			t.Errorf("unexpected item in table: %v", v)
		}
		ids = append(ids, v["Id"].(string))
	}

	slices.Sort(ids)
	if expected := []string{
		"EMAIL#opg-use-an-lpa+test-user1@digital.justice.gov.uk",
		"EMAIL#opg-use-an-lpa+test-user@digital.justice.gov.uk",
		"IDENTITY#urn:fdc:mock-one-login:2023:A1ijOOniZMZ7rk+IpkVrwpCuzyehufQDPR1XnyCKj3o=",
	}; !slices.Equal(ids, expected) {
		t.Errorf("table items do not match expected.\nGot: %v\nExpected: %v", ids, expected)
	}

	// and the bucket should be empty
	listOutput, err := s3Client.ListObjects(ctx, &s3.ListObjectsInput{
		Bucket: aws.String(bucketName),
	})
	if err != nil {
		t.Errorf("error returned: %v", err)
	}

	var keys []string
	for _, item := range listOutput.Contents {
		keys = append(keys, *item.Key)
	}

	slices.Sort(keys)
	if expected := []string{
		"AWSDynamoDB/01765968403795-d1d972cf/manifest-files.json",
		"AWSDynamoDB/01765968403795-d1d972cf/manifest-summary.json",
	}; !slices.Equal(keys, expected) {
		t.Errorf("bucket items do not match expected.\nGot: %v\nExpected: %v", keys, expected)
	}
}
