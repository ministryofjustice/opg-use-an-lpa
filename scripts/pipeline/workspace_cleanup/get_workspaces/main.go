// +build linux darwin

package main

import (
	"fmt"
	"log"
	"os"

	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/aws/credentials/stscreds"
	"github.com/aws/aws-sdk-go/aws/session"
	"github.com/aws/aws-sdk-go/service/dynamodb"
	"github.com/aws/aws-sdk-go/service/dynamodb/dynamodbattribute"
)

func exitWithError(err error) {
	fmt.Fprintln(os.Stderr, err)
	os.Exit(1)
}

func main() {
	sess, err := session.NewSession()
	if err != nil {
		log.Fatalln(err)
	}
	RoleArn := ""
	if len(os.Getenv("CI")) > 0 {
		RoleArn = "arn:aws:iam::367815980639:role/opg-use-an-lpa-ci"
	} else {
		RoleArn = "arn:aws:iam::367815980639:role/operator"
	}

	creds := stscreds.NewCredentials(sess, RoleArn)
	awsConfig := aws.Config{Credentials: creds, Region: aws.String("eu-west-1")}

	svc := dynamodb.New(sess, &awsConfig)

	params := &dynamodb.ScanInput{
		TableName: aws.String("WorkspaceCleanup"),
	}

	result, err := svc.Scan(params)
	if err != nil {
		exitWithError(fmt.Errorf("failed to make Query API call, %v", err))
	}

	items := []Item{}

	err = dynamodbattribute.UnmarshalListOfMaps(result.Items, &items)
	if err != nil {
		exitWithError(fmt.Errorf("failed to unmarshal Query result items, %v", err))
	}

	for i, item := range items {
		fmt.Print(item.WorkspaceName, " ")
		i++
	}

}

type Item struct {
	WorkspaceName string
}
