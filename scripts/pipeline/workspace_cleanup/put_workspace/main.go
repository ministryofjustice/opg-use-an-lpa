// +build linux darwin

package main

import (
	"fmt"
	"os"
	"flag"

	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/aws/credentials/stscreds"
	"github.com/aws/aws-sdk-go/aws/session"
	"github.com/aws/aws-sdk-go/service/dynamodb"
	"github.com/aws/aws-sdk-go/service/dynamodb/dynamodbattribute"
	"log"
)

func exitWithError(err error) {
	fmt.Fprintln(os.Stderr, err)
	os.Exit(1)
}

func main() {
	flag.Usage = func() {
		fmt.Println("Usage: stabilizer")
		flag.PrintDefaults()
	}

	var workspace_name string
	flag.StringVar(&workspace_name, "workspace", "", "name of workspace to mark for clean up")
	flag.Parse()


	sess, err := session.NewSession()
	if err != nil {
		log.Fatalln(err)
	}
	role_arn := ""
	if len(os.Getenv("CI")) > 0 {
		role_arn = "arn:aws:iam::367815980639:role/opg-use-an-lpa-ci"
	} else {
		role_arn = "arn:aws:iam::367815980639:role/operator"
	}

	creds := stscreds.NewCredentials(sess, role_arn)
	awsConfig := aws.Config{Credentials: creds, Region: aws.String("eu-west-1")}

	svc := dynamodb.New(sess, &awsConfig)

	type Workspace struct {
		WorkspaceName string
	}

	item := Workspace{
		WorkspaceName: workspace_name,
	}

	workspace_to_put, err := dynamodbattribute.MarshalMap(item)
	if err != nil {
		fmt.Println("Got error marshalling Workspace item:")
		fmt.Println(err.Error())
		os.Exit(1)
	}

	input := &dynamodb.PutItemInput{
		Item:      workspace_to_put,
		TableName: aws.String("WorkspaceCleanup"),
	}

	_, err = svc.PutItem(input)
	if err != nil {
		fmt.Println("Got error calling PutItem:")
		fmt.Println(err.Error())
		os.Exit(1)
	}

	fmt.Println("Successfully added '" + item.WorkspaceName + " for workspace cleanup")

}
