package main

import (
	"compress/gzip"
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"log/slog"
	"os"
	"slices"

	"github.com/aws/aws-lambda-go/lambda"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/aws/aws-sdk-go-v2/service/s3"
	s3types "github.com/aws/aws-sdk-go-v2/service/s3/types"
)

var (
	s3Client     *BucketClient
	dynamoClient *TableClient
)

func main() {
	var (
		ctx = context.Background()
		err error

		awsBaseURL = os.Getenv("AWS_BASE_URL")
		tableName  = os.Getenv("TABLE_NAME")
		bucketName = os.Getenv("BUCKET_NAME")
	)

	logger := slog.New(slog.NewJSONHandler(os.Stdout, nil))
	slog.SetDefault(logger)

	cfg, err := config.LoadDefaultConfig(ctx)
	if err != nil {
		slog.Error(fmt.Sprintf("load config: %v", err))
		return
	}

	if awsBaseURL != "" {
		cfg.BaseEndpoint = aws.String(awsBaseURL)
	}

	s3Client = NewBucketClient(cfg, bucketName)
	dynamoClient = NewTableClient(cfg, tableName)

	lambda.Start(run)
}

type Event struct {
	ManifestPath string
}

func run(ctx context.Context, event Event) error {
	slog.Info("started run", slog.String("manifestPath", event.ManifestPath))

	if err := processManifest(ctx, s3Client, dynamoClient, event.ManifestPath); err != nil {
		slog.Error(fmt.Sprintf("error processing manifest: %v", err))
		return err
	}

	return nil
}

type Item struct {
	Item ActorUser
}

type ActorUser struct {
	Id       ValueS
	Identity ValueS
	Email    ValueS
}

type ValueS struct {
	S string
}

type ManifestRecord struct {
	ItemCount     int    `json:"itemCount"`
	DataFileS3Key string `json:"dataFileS3Key"`
}

func processManifest(ctx context.Context, s3Client *BucketClient, dynamoClient *TableClient, manifestFilesPath string) error {
	manifestFilesOutput, err := s3Client.GetObject(ctx, manifestFilesPath)
	if err != nil {
		return fmt.Errorf("get manifest file: %w", err)
	}
	defer manifestFilesOutput.Body.Close()

	decoder := json.NewDecoder(manifestFilesOutput.Body)
	for {
		var record ManifestRecord
		if err := decoder.Decode(&record); err != nil {
			if errors.Is(err, io.EOF) {
				slog.Info("done")
				return nil
			}

			return fmt.Errorf("decode manifest file: %w", err)
		}

		if record.ItemCount == 0 {
			if _, err := s3Client.DeleteObject(ctx, record.DataFileS3Key); err != nil {
				return fmt.Errorf("delete file '%s': %w", record.DataFileS3Key, err)
			}
			slog.Info("deleted empty file", slog.String("key", record.DataFileS3Key))
		} else {
			if err := processFile(ctx, s3Client, dynamoClient, record.DataFileS3Key); err != nil {
				return fmt.Errorf("process file '%s': %w", record.DataFileS3Key, err)
			}
		}
	}
}

func processFile(
	ctx context.Context,
	s3Client *BucketClient,
	dynamodbClient *TableClient,
	key string,
) error {
	dataFileOutput, err := s3Client.GetObject(ctx, key)
	if err != nil {
		var notFound *s3types.NotFound
		if errors.As(err, &notFound) {
			slog.Info("already processed file", slog.String("key", key))
			return nil
		}

		var noSuchKey *s3types.NoSuchKey
		if errors.As(err, &noSuchKey) {
			slog.Info("already processed file", slog.String("key", key))
			return nil
		}

		return err
	}
	defer dataFileOutput.Body.Close()

	reader, err := gzip.NewReader(dataFileOutput.Body)
	if err != nil {
		return fmt.Errorf("gzip decode: %w", err)
	}

	decoder := json.NewDecoder(reader)

	var writeRequests []types.WriteRequest
	for stop := false; !stop; {
		var v Item
		if err := decoder.Decode(&v); err != nil {
			if errors.Is(err, io.EOF) {
				slog.Info("decoded file", slog.String("key", key))
				stop = true
			} else {
				return fmt.Errorf("decode json: %w", err)
			}
		}

		if identity := v.Item.Identity.S; identity != "" {
			writeRequests = append(writeRequests, types.WriteRequest{
				PutRequest: &types.PutRequest{
					Item: map[string]types.AttributeValue{
						"Id": &types.AttributeValueMemberS{Value: "IDENTITY#" + identity},
					},
				},
			})
		}

		if email := v.Item.Email.S; email != "" {
			writeRequests = append(writeRequests, types.WriteRequest{
				PutRequest: &types.PutRequest{
					Item: map[string]types.AttributeValue{
						"Id": &types.AttributeValueMemberS{Value: "EMAIL#" + email},
					},
				},
			})
		}
	}

	// BatchWriteItem has a limit of 25 items per request, so process in chunks.
	for chunk := range slices.Chunk(writeRequests, 25) {
		output, err := dynamodbClient.BatchWriteItem(ctx, chunk)
		if err != nil {
			return fmt.Errorf("batch write item: %w", err)
		}

		if unprocessedItems := dynamodbClient.UnprocessedItems(output); len(unprocessedItems) > 0 {
			for _, unprocessed := range unprocessedItems {
				var id string
				attributevalue.Unmarshal(unprocessed.PutRequest.Item["Id"], &id)
				slog.Warn("unprocessed item", slog.Any("Id", id))
			}

			// For now return if this does happen so we can figure out why, and
			// fix. Probably would be better than continuing as it may happen again...
			return errors.New("unprocessed items")
		}
	}
	slog.Info("processed file", slog.String("key", key))

	if _, err := s3Client.DeleteObject(ctx, key); err != nil {
		return fmt.Errorf("delete file '%s': %w", key, err)
	}
	slog.Info("deleted file", slog.String("key", key))

	return nil
}

// A BucketClient is a s3.Client scoped for use in the named bucket.
type BucketClient struct {
	client     *s3.Client
	bucketName string
}

func NewBucketClient(cfg aws.Config, bucketName string) *BucketClient {
	return &BucketClient{
		client: s3.NewFromConfig(cfg, func(opts *s3.Options) {
			opts.UsePathStyle = true
		}),
		bucketName: bucketName,
	}
}

func (c *BucketClient) GetObject(ctx context.Context, key string) (*s3.GetObjectOutput, error) {
	return c.client.GetObject(ctx, &s3.GetObjectInput{
		Bucket: aws.String(c.bucketName),
		Key:    aws.String(key),
	})
}

func (c *BucketClient) DeleteObject(ctx context.Context, key string) (*s3.DeleteObjectOutput, error) {
	return c.client.DeleteObject(ctx, &s3.DeleteObjectInput{
		Bucket: aws.String(c.bucketName),
		Key:    aws.String(key),
	})
}

// A TableClient is a dynamodb.Client scoped for use on the named table.
type TableClient struct {
	client    *dynamodb.Client
	tableName string
}

func NewTableClient(cfg aws.Config, tableName string) *TableClient {
	return &TableClient{
		client:    dynamodb.NewFromConfig(cfg),
		tableName: tableName,
	}
}

func (c *TableClient) BatchWriteItem(ctx context.Context, writeRequests []types.WriteRequest) (*dynamodb.BatchWriteItemOutput, error) {
	return c.client.BatchWriteItem(ctx, &dynamodb.BatchWriteItemInput{
		RequestItems: map[string][]types.WriteRequest{
			c.tableName: writeRequests,
		},
	})
}

func (c *TableClient) UnprocessedItems(output *dynamodb.BatchWriteItemOutput) []types.WriteRequest {
	return output.UnprocessedItems[c.tableName]
}
