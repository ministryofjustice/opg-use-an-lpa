package main

import (
	"encoding/json"
	"fmt"

	"github.com/aws/aws-lambda-go/events"
	"github.com/aws/aws-lambda-go/lambda"
)

type Response struct {
  StatusCode  int       `json:"statusCode"`
  Headers     map[string]string  `json:"headers"`
  Body        string    `json:"body"`
}

func handler(event events.SQSEvent) (interface{}, error) {
	res := &Response{
      StatusCode: 1,
      Headers: map[string]string{"Content-Type": "application/json"},
      Body: "Hello World",
    }
	for _, record := range event.Records {
		err := processMessage(record)
		if err != nil {
			return nil, err
		}
	}
	fmt.Println("done")
    content, _ := json.Marshal(res)
    return string(content), nil
}

func processMessage(record events.SQSMessage) error {
	fmt.Printf("Processed message %s\n", record.Body)
	fmt.Printf("Hello, world!\n")
	return nil
}

func main() {
	lambda.Start(handler)
	fmt.Printf("Hello, world!\n")
}
