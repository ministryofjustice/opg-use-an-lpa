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
	Identity  string `dynamodbav:"Identity,omitempty"`
	Password  string `dynamodbav:"Password,omitempty"`
	LastLogin string
	Lpas      []lpa `dynamodbav:"-"`
	Comment   string
}

type lpa struct {
	Id        string
	UserId    string
	ActorId   string
	LpaUid    string
	SiriusUid string
	Comment   string
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

	var accountsWithLpas, accountsWithOldLogin, identityAccounts int
	var accounts []account
	for i := 0; i < 10000; i++ {
		email := faker.Email(options.WithCustomDomain("example.org"))

		account := account{
			Id:      uuid.NewString(),
			Email:   email,
			Lpas:    []lpa{},
			Comment: "Seeded Account: Unused accounts bulk seeding",
		}

		if randomChanceOne(4) {
			account.LastLogin = LastLoginUnused()
			accountsWithOldLogin++
		} else {
			account.LastLogin = LastLoginRecent()
		}

		if randomChanceOne(4) {
			account.Password = uuid.NewString()
		} else {
			h := sha256.New()
			h.Write([]byte(email))
			encodedEmail := base64.StdEncoding.EncodeToString(h.Sum(nil))
			account.Identity = "urn:fdc:mock-one-login:2023:" + encodedEmail
			identityAccounts++
		}

		if randomChanceOne(2) {
			actorId := rand.Intn(99999999)
			siriusUid := rand.Intn(99999999)
			account.Lpas = append(account.Lpas, lpa{
				Id:        uuid.NewString(),
				UserId:    account.Id,
				ActorId:   fmt.Sprintf("7000%08d", actorId),
				LpaUid:    "",
				SiriusUid: fmt.Sprintf("7000%08d", siriusUid),
				Comment:   "Seeded LPA: Unused accounts bulk seeding",
			})
			accountsWithLpas++
		}

		accounts = append(accounts, account)
	}

	writeData(cfg.tablePrefix, accounts)

	logger.Info(
		fmt.Sprintf(
			"Generated %d accounts (%d with one LPA, %d with last login over 6 months ago, %d with identity pinning, %d with a password)",
			len(accounts),
			accountsWithLpas,
			accountsWithOldLogin,
			identityAccounts,
			len(accounts)-identityAccounts,
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

		// identity pinning row, as written by the API when an account is created (service-api ActorUsers::add)
		if acc.Identity != "" {
			items = append(items, types.TransactWriteItem{
				Put: &types.Put{
					Item: map[string]types.AttributeValue{
						"Id": &types.AttributeValueMemberS{Value: "IDENTITY#" + acc.Identity},
					},
					TableName:           &tableName,
					ConditionExpression: &notExists,
				},
			})
		}

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
	now := time.Now()
	sixMonthsAgo := now.AddDate(0, -6, 0)
	oneYearAgo := now.AddDate(-1, 0, 0)

	return randomTimeBetween(oneYearAgo, sixMonthsAgo)
}

func LastLoginRecent() string {
	now := time.Now()
	sixMonthsAgo := now.AddDate(0, -6, 0)

	return randomTimeBetween(sixMonthsAgo, now)
}

func randomTimeBetween(start, end time.Time) string {
	randomTime := rand.Int63n(end.Unix()-start.Unix()) + start.Unix()

	return time.Unix(randomTime, 0).UTC().Format(time.RFC3339)
}

func randomChanceOne(in int) bool {
	return rand.Intn(in) == 0
}
