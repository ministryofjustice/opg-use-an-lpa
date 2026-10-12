package main

import (
	"context"
	"crypto/sha256"
	"encoding/base64"
	"errors"
	"flag"
	"fmt"
	"log/slog"
	"math/rand"
	"os"
	"time"

	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/go-faker/faker/v4"
	"github.com/go-faker/faker/v4/pkg/options"
	"github.com/google/uuid"
)

var (
	dynamoClient *dynamodb.Client
	ctx          context.Context
	logger       *slog.Logger
)

type account struct {
	Id        string
	Email     string
	Identity  string
	LastLogin string
	Lpas      []lpa `dynamodbav:"-"`
	Comment   string
}

type lpa struct {
	Id          string
	UserId      string
	ActorId     string
	LpaUid      string
	SiriusUid   string
	ViewerCodes []viewercode `dynamodbav:"-"`
	Comment     string
}

type viewercode struct {
	ViewerCode   string
	SiriusUid    string
	UserLpaActor string
	Comment      string
}

type cfg struct {
	tablePrefix string
	endpointUrl string
}

func main() {
	ctx = context.Background()

	logger = slog.New(slog.NewJSONHandler(os.Stdout, nil))
	slog.SetDefault(logger)

	os.Exit(run(os.Args[1:], ctx))
}

func run(args []string, ctx context.Context) int {
	fs := flag.NewFlagSet("seed_unused_accounts", flag.ContinueOnError)
	fs.SetOutput(os.Stderr)

	fs.Usage = func() {
		fmt.Fprintf(fs.Output(), "Usage: %s [flags]\n", fs.Name())
		fmt.Fprintln(fs.Output(), "Flags:")
		fs.PrintDefaults()
	}

	cfg := cfg{}
	fs.StringVar(&cfg.tablePrefix, "p", "demo", "The prefix to use for the DynamoDB tables")
	fs.StringVar(&cfg.endpointUrl, "e", "", "A url to override the AWS endpoint with")

	if err := fs.Parse(args); err != nil {
		if errors.Is(err, flag.ErrHelp) {
			return 0
		}
		return 2
	}

	if cfg.tablePrefix != "" {
		cfg.tablePrefix = fmt.Sprintf("%s-", cfg.tablePrefix)
	}

	awsCfg, err := config.LoadDefaultConfig(ctx)
	if err != nil {
		slog.Error(fmt.Sprintf("load config: %v", err))
		return 3
	}

	if cfg.endpointUrl != "" {
		awsCfg.BaseEndpoint = &cfg.endpointUrl
	}

	dynamoClient = dynamodb.NewFromConfig(awsCfg)

	// Generate initial accounts - no LPAs, last login 6+ months ago
	var accounts []account
	for i := 0; i < 10000; i++ {
		email := faker.Email(options.WithCustomDomain("example.org"))
		h := sha256.New()
		h.Write([]byte(email))
		encodedEmail := base64.StdEncoding.EncodeToString(h.Sum(nil))

		account := account{
			Id:        uuid.NewString(),
			Email:     email,
			Identity:  "urn:fdc:mock-one-login:2023:" + encodedEmail,
			LastLogin: LastLoginUnused(),
			Lpas:      []lpa{},
			Comment:   "Seeded Account: Unused accounts bulk seeding",
		}

		accounts = append(accounts, account)
	}

	// Write the seeded accounts (no LPA generation, no duplication)
	writeData(cfg.tablePrefix, accounts)

	logger.Info(
		fmt.Sprintf(
			"Generated %d accounts with no LPAs and last login 6+ months ago",
			len(accounts),
		),
	)

	return 0
}

func writeData(tablePrefix string, accounts []account) {
	notExists := "attribute_not_exists(Id)"

	for _, acc := range accounts {
		items := []types.TransactWriteItem{}

		item, err := attributevalue.MarshalMap(acc)
		if err != nil {
			panic(err)
		}

		tableName := fmt.Sprintf("%sActorUsers", tablePrefix)
		items = append(items, types.TransactWriteItem{
			Put: &types.Put{
				Item:                item,
				TableName:           &tableName,
				ConditionExpression: &notExists,
			},
		})

		for _, lpa := range acc.Lpas {
			item, err := attributevalue.MarshalMap(lpa)
			if err != nil {
				panic(err)
			}

			tableName := fmt.Sprintf("%sUserLpaActorMap", tablePrefix)
			items = append(items, types.TransactWriteItem{
				Put: &types.Put{
					Item:                item,
					TableName:           &tableName,
					ConditionExpression: &notExists,
				},
			})

			for _, viewerCode := range lpa.ViewerCodes {
				item, err := attributevalue.MarshalMap(viewerCode)
				if err != nil {
					panic(err)
				}

				tableName := fmt.Sprintf("%sViewerCodes", tablePrefix)
				vcNotExists := "attribute_not_exists(ViewerCode)"
				items = append(items, types.TransactWriteItem{
					Put: &types.Put{
						Item:                item,
						TableName:           &tableName,
						ConditionExpression: &vcNotExists,
					},
				})
			}
		}

		transactionItems := &dynamodb.TransactWriteItemsInput{
			TransactItems: items,
		}

		_, err = dynamoClient.TransactWriteItems(ctx, transactionItems)
		if err != nil {
			logger.Error("Transaction failed",
				slog.String("identity", acc.Identity),
				slog.Any("error", err),
			)
		} else {
			logger.Debug("Added account", slog.String("identity", acc.Identity))
		}
	}
}

func LastLoginUnused() string {
	location, _ := time.LoadLocation("Europe/London")

	// Calculate 6 months ago and 1 year ago from now
	now := time.Now().In(location)
	sixMonthsAgo := now.AddDate(0, -6, 0)
	oneYearAgo := now.AddDate(-1, 0, 0)

	// Generate random time between 1 year ago and 6 months ago
	startTimestamp := oneYearAgo.Unix()
	endTimestamp := sixMonthsAgo.Unix()
	randomTime := rand.Int63n(endTimestamp-startTimestamp) + startTimestamp

	return time.Unix(randomTime, 0).In(time.UTC).Format(time.RFC3339)
}


