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
	fs := flag.NewFlagSet("seed_duplicate_accounts", flag.ContinueOnError)
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
		awsCfg.BaseEndpoint = new(cfg.endpointUrl)
	}

	dynamoClient = dynamodb.NewFromConfig(awsCfg)

	// Generate initial accounts
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
			LastLogin: LastLogin(),
			Lpas:      []lpa{},
			Comment:   "Seeded Account: Duplicate user bulk seeding",
		}

		accounts = append(accounts, account)
	}

	// add 1 - 3 lpas to 33% of those accounts
	accounts, lpasGenerated, addedToAccounts, viewerCodes := generateLpas(accounts)

	// duplicate 10% of the accounts
	accounts, duplicatedAccounts := duplicateAccounts(accounts)

	writeData(cfg.tablePrefix, accounts)

	logger.Info(
		fmt.Sprintf(
			"Generated %d total accounts with %d LPAs added to %d of them, %d viewercodes and %d duplicated accounts",
			len(accounts),
			lpasGenerated,
			addedToAccounts,
			viewerCodes,
			duplicatedAccounts,
		),
	)

	return 0
}

func writeData(tablePrefix string, accounts []account) {
	for _, acc := range accounts {
		items := []types.TransactWriteItem{}

		item, err := attributevalue.MarshalMap(acc)
		if err != nil {
			panic(err)
		}

		items = append(items, types.TransactWriteItem{
			Put: &types.Put{
				Item:                item,
				TableName:           new(fmt.Sprintf("%sActorUsers", tablePrefix)),
				ConditionExpression: new("attribute_not_exists(Id)"),
			},
		})

		for _, lpa := range acc.Lpas {
			item, err := attributevalue.MarshalMap(lpa)
			if err != nil {
				panic(err)
			}

			items = append(items, types.TransactWriteItem{
				Put: &types.Put{
					Item:                item,
					TableName:           new(fmt.Sprintf("%sUserLpaActorMap", tablePrefix)),
					ConditionExpression: new("attribute_not_exists(Id)"),
				},
			})

			for _, viewerCode := range lpa.ViewerCodes {
				item, err := attributevalue.MarshalMap(viewerCode)
				if err != nil {
					panic(err)
				}

				items = append(items, types.TransactWriteItem{
					Put: &types.Put{
						Item:                item,
						TableName:           new(fmt.Sprintf("%sViewerCodes", tablePrefix)),
						ConditionExpression: new("attribute_not_exists(ViewerCode)"),
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

func LastLogin() string {
	location, _ := time.LoadLocation("Europe/London")

	startdate := time.Date(2019, 07, 1, 0, 0, 0, 0, location).Unix()
	now := time.Now().Unix()
	randomTime := rand.Int63n(now-startdate) + startdate

	return time.Unix(randomTime, 0).In(time.UTC).Format(time.RFC3339)
}

func generateLpas(accounts []account) ([]account, int, int, int) {
	var data []account
	lpasGenerated := 0
	addedToAccounts := 0
	viewerCodes := 0

	for _, acc := range accounts {
		acc.Lpas = []lpa{}

		if randomChanceOne(3) {
			for i := 0; i < (rand.Intn(3) + 1); i++ {
				actorId := rand.Intn(99999999)
				siriusUid := rand.Intn(99999999)

				lpa := lpa{
					Id:        uuid.NewString(),
					UserId:    acc.Id,
					ActorId:   fmt.Sprintf("7000%08d", actorId),
					LpaUid:    "",
					SiriusUid: fmt.Sprintf("7000%08d", siriusUid),
					Comment:   "Seeded LPA: Duplicate user bulk seeding",
				}

				// LPA has viewercodes. It's probably lower than 1 in 10 tbh.
				if randomChanceOne(10) {
					lpa.ViewerCodes = generateViewerCodes(lpa.Id, lpa.SiriusUid)
					viewerCodes = viewerCodes + len(lpa.ViewerCodes)
				}

				acc.Lpas = append(acc.Lpas, lpa)
				lpasGenerated++
			}

			addedToAccounts++
		}

		data = append(data, acc)
	}

	return data, lpasGenerated, addedToAccounts, viewerCodes
}

func generateViewerCodes(id string, siriusUid string) []viewercode {
	var codes []viewercode

	for i := 0; i < (rand.Intn(15) + 1); i++ {
		code := viewercode{
			ViewerCode:   fmt.Sprintf("%012d", rand.Intn(99999999)),
			SiriusUid:    siriusUid,
			UserLpaActor: id,
			Comment:      "Seeded Viewercode: Duplicate user bulk seeding",
		}

		codes = append(codes, code)
	}

	return codes
}

func randomChanceOne(in int) bool {
	result := rand.Intn(in) + 1
	return result == in
}

func duplicateAccounts(accounts []account) ([]account, int) {
	var duplicated []account
	duplicatedAccounts := 0

	for _, acc := range accounts {
		duplicated = append(duplicated, acc)

		if randomChanceOne(10) {
			duplicate := acc
			duplicate.Id = uuid.NewString()
			duplicate.LastLogin = LastLogin()
			duplicate.Lpas = []lpa{}

			// the duplicate account also has a lpa in it.
			if len(acc.Lpas) > 0 && randomChanceOne(3) {
				duplicateLpa := acc.Lpas[0]
				duplicateLpa.Id = uuid.NewString()
				duplicateLpa.UserId = duplicate.Id
				duplicateLpa.ViewerCodes = []viewercode{}
				duplicate.Lpas = []lpa{duplicateLpa} // just one will do
			}

			duplicated = append(duplicated, duplicate)
			duplicatedAccounts++

			// some duplicate accounts have more than one duplicate
			if !randomChanceOne(4) {
				continue
			}

			secondDuplicate := duplicate
			secondDuplicate.Id = uuid.NewString()
			secondDuplicate.LastLogin = LastLogin()
			secondDuplicate.Lpas = []lpa{}
			duplicated = append(duplicated, secondDuplicate)
			duplicatedAccounts++
		}

	}

	return duplicated, duplicatedAccounts
}
