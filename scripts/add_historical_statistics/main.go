package main

import (
	"context"
	"encoding/csv"
	"encoding/json"
	"flag"
	"fmt"
	"io/ioutil"
	"log"
	"os"
	"strings"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/credentials/stscreds"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/aws/aws-sdk-go-v2/service/sts"
)

func readCsvFile(filePath string) [][]string {
	f, err := os.Open(filePath)
	if err != nil {
		log.Fatal("unable to read input file "+filePath, err)
	}
	defer f.Close()

	csvReader := csv.NewReader(f)
	records, err := csvReader.ReadAll()

	if err != nil {
		log.Fatal("Unable to parse file as CSV for "+filePath, err)
	}

	return records
}

func main() {
	flag.Usage = func() {
		fmt.Println("Usage: Add historical data to dynamo db")
		flag.PrintDefaults()
	}

	var roleToAssume string
	var tableName string
	var endpoint string
	var outputToJson bool
	var jsonFileName string
	flag.StringVar(&roleToAssume, "role", "arn:aws:iam::367815980639:role/operator", "Role to assume when signing requests for using PutItem to dynamo")
	flag.StringVar(&tableName, "table", "demo-Stats", "Table to add the statistics to in format {environment}-{tablename}")
	flag.StringVar(&endpoint, "endpoint", "", "Endpoint for dynamo")
	flag.BoolVar(&outputToJson, "outputJson", false, "Optional flag to save as json file for testing")
	flag.StringVar(&jsonFileName, "jsonFileName", "test.json", "Optional flag to name the saved json file")
	flag.Parse()

	fmt.Println(roleToAssume)

	eventCodeStats := readCsvFile("stats.csv")
	recordsMap := eventCodeStatsToMap(eventCodeStats)

	if outputToJson {
		b, err := json.MarshalIndent(recordsMap, "", "  ")
		if err != nil {
			fmt.Println(err)
		}
		err = ioutil.WriteFile(jsonFileName, b, 0644)
		if err != nil {
			fmt.Println(err)
		}
	}

	config, err := config.LoadDefaultConfig(context.TODO(), config.WithRegion("eu-west-1"))

	if err != nil {
		panic(err)
	}

	svc := dynamodb.NewFromConfig(config, func(o *dynamodb.Options) {
		if roleToAssume != "" {
			client := sts.NewFromConfig(config)
			roleProvider := stscreds.NewAssumeRoleProvider(client, roleToAssume)
			o.Credentials = roleProvider
		}
		if endpoint != "" {
			o.EndpointResolver = dynamodb.EndpointResolverFromURL(endpoint)
		}

	})

	requestItems := []*dynamodb.PutItemInput{}

	for key, item := range recordsMap {

		dynamoItem := map[string]types.AttributeValue{
			"TimePeriod": &types.AttributeValueMemberS{Value: key},
		}

		for itemKey, itemValue := range item {
			if itemKey != "" {
				dynamoItem[itemKey] = &types.AttributeValueMemberN{Value: itemValue}
			}
		}

		request := &dynamodb.PutItemInput{
			TableName: aws.String(tableName),
			Item:      dynamoItem,
		}
		requestItems = append(requestItems, request)
	}

	for _, request := range requestItems {

		fmt.Println(request.Item["TimePeriod"])

		out, err := svc.PutItem(context.TODO(), request)

		if err != nil {
			panic(err)
		}
	}

	fmt.Println("Added", len(requestItems), "items")
}

func eventCodeStatsToMap(records [][]string) map[string]map[string]string {
	monthToEventCodeStats := map[string]map[string]string{}

	for i := 1; i < len(records); i++ {

		eventCodeName := records[i][0]

		for j := 1; j < len(records[i]); j++ {

			timePeriod := records[0][j]
			lastDash := strings.LastIndex(timePeriod, "-")
			if lastDash != -1 {
				timePeriod = timePeriod[:lastDash]
			}

			if monthToEventCodeStats[timePeriod] == nil {
				monthToEventCodeStats[timePeriod] = map[string]string{}
			}

			frequency := records[i][j]

			if frequency == "" {
				frequency = "0"
			}
			monthToEventCodeStats[timePeriod][eventCodeName] = strings.ReplaceAll(frequency, ",", "")

		}
	}

	return monthToEventCodeStats
}
