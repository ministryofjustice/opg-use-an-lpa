package server

import (
	"fmt"
	"log"
	"net/http"

	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/aws/session"
	"github.com/aws/aws-sdk-go/service/dynamodb"
	"github.com/aws/aws-sdk-go/service/dynamodb/dynamodbattribute"
	"github.com/aws/aws-sdk-go/service/dynamodb/expression"
)

const ListUsersRoute = "/users"

type User struct {
	Email    string
	Password string
}

// ListUsersHandler shows a list of all users registered
func ListUsersHandler(tmpl Template) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var users []User

		if r.Method == http.MethodPost {
			filt := expression.Name("Email").Contains(r.FormValue("email"))
			proj := expression.NamesList(expression.Name("Email"))

			expr, err := expression.NewBuilder().WithFilter(filt).WithProjection(proj).Build()
			if err != nil {
				fmt.Println("Got error building expression:")
				fmt.Println(err.Error())
			}

			sess := session.Must(session.NewSessionWithOptions(session.Options{
				SharedConfigState: session.SharedConfigEnable,
			}))

			svc := dynamodb.New(sess, &aws.Config{Endpoint: aws.String("http://localhost:8000")})

			params := &dynamodb.ScanInput{
				ExpressionAttributeNames:  expr.Names(),
				ExpressionAttributeValues: expr.Values(),
				FilterExpression:          expr.Filter(),
				ProjectionExpression:      expr.Projection(),
				TableName:                 aws.String("ActorUsers"),
			}

			// Make the DynamoDB Query API call
			result, err := svc.Scan(params)
			if err != nil {
				fmt.Println("Query API call failed:")
				fmt.Println((err.Error()))
			}

			for _, i := range result.Items {
				user := User{}

				err = dynamodbattribute.UnmarshalMap(i, &user)

				if err != nil {
					fmt.Println("Got error unmarshalling:")
					fmt.Println(err.Error())
				}

				users = append(users, user)
			}

		}

		data := struct {
			Users []User
			Query string
		}{users, r.FormValue("email")}

		if err := tmpl.ExecuteTemplate(w, "page", data); err != nil {
			log.Println(err)
		}
	}
}
