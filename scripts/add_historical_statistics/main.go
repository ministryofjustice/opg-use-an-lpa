package main

import (
	"context"
	"encoding/csv"
	"encoding/json"
	"fmt"
	"log"
	"os"
	"strings"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
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

	dbEndpoint := os.Args[0]
	if dbEndpoint == "" {
		dbEndpoint = "http://localhost:8000"
	}

	eventCodeStats := readCsvFile("stats.csv")
	recordsMap := eventCodeStatsToMap(eventCodeStats)
	b, err := json.MarshalIndent(recordsMap, "", "  ")
	if err != nil {
		fmt.Println("error:", err)
	}
	//_ = ioutil.WriteFile("test.json", b, 0644)

	config, _ := config.LoadDefaultConfig(context.TODO(), config.WithRegion("eu-west-1"))

	svc := dynamodb.NewFromConfig(config, func(o *dynamodb.Options) {
		if dbEndpoint != "" {
			o.EndpointResolver = dynamodb.EndpointResolverFromURL(dbEndpoint)
		}
	})

	tableName := "Stats"

	requestItems := []*dynamodb.PutItemInput{}

	for key, item := range recordsMap {

		fmt.Println(key)

		dynamoItem := map[string]types.AttributeValue{
			"TimePeriod": &types.AttributeValueMemberS{Value: key},
		}

		for itemKey, itemValue := range item {
			if itemKey != "" {
				dynamoItem[itemKey] = &types.AttributeValueMemberS{Value: itemValue}
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
			fmt.Println(err)
			panic(err)
		}

		fmt.Println(out.Attributes)
	}

}

func eventCodeStatsToMap(records [][]string) map[string]map[string]string {
	monthToEventCodeStats := map[string]map[string]string{}

	for i := 1; i < len(records); i++ {
		eventCodeName := records[i][0]

		for j := 1; j < len(records[i]); j++ {
			timePeriod := records[0][j]

			if monthToEventCodeStats[timePeriod] == nil {
				monthToEventCodeStats[timePeriod] = map[string]string{}
			}

			frequency := records[i][j]

			if frequency != "" {
				monthToEventCodeStats[timePeriod][eventCodeName] = strings.ReplaceAll(frequency, ",", "")
			}
		}
	}

	return monthToEventCodeStats
}
