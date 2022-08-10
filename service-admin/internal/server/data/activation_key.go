package data

import (
	"bytes"
	"context"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"errors"
	"fmt"
	"io/ioutil"
	"net/http"
	"time"

	"github.com/aws/aws-sdk-go-v2/aws"
	v4 "github.com/aws/aws-sdk-go-v2/aws/signer/v4"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/rs/zerolog/log"
)

type ActivationKeyService interface {
	GetActivationKeyFromCodes(context.Context, string) (*ActivationKeys, error)
}

type ActivationKeys []struct {
	Active          bool   `json:"active"`
	Actor           string `json:"actor"`
	Code            string `json:"code"`
	Dob             string `json:"dob"`
	ExpiryDate      int    `json:"expiry_date" dynamodbav:"expiry_date"`
	GeneratedDate   string `json:"generated_date" dynamodbav:"generated_date"`
	LastUpdatedDate string `json:"last_updated_date" dynamodbav:"last_updated_date"`
	Lpa             string `json:"lpa"`
	StatusDetails   string `json:"status_details" dynamodbav:"status_details"`
}

type OnlineActivationKeyService struct {
	awsSigner   *v4.Signer
	credentials aws.Credentials
	codesAPIURL string
}

func NewOnlineActivationKeyService(awsSigner *v4.Signer, credentials aws.Credentials, codesAPIURL string) ActivationKeyService {
	return &OnlineActivationKeyService{awsSigner: awsSigner, credentials: credentials, codesAPIURL: codesAPIURL}
}

func (aks *OnlineActivationKeyService) GetActivationKeyFromCodes(ctx context.Context, activationKey string) (returnedKeys *ActivationKeys, returnedErr error) {

	jsonStr := []byte(fmt.Sprintf(`{"code":"%s"}`, activationKey))

	r, err := http.NewRequest(http.MethodPost, aks.codesAPIURL, bytes.NewBuffer(jsonStr))
	r.Header.Set("Content-Type", "application/json")

	//calculate hash of request body
	hasher := sha256.New()
	hasher.Write(jsonStr)
	shaHash := hex.EncodeToString(hasher.Sum(nil))

	log.Info().Msgf("Sha hash : %s" + shaHash)

	signError := aks.awsSigner.SignHTTP(ctx, aks.credentials, r, shaHash, "execute-api", "eu-west-1", time.Now())

	if signError != nil {
		log.Info().Msgf("Error Signing %v", signError)
	}

	client := http.Client{}
	resp, err := client.Do(r)
	if err != nil {
		return nil, errors.New("Cannot connect to server")
	}

	if resp.StatusCode == 200 {
		data, err := ioutil.ReadAll(resp.Body)
		if err != nil {
			return nil, errors.New("Error parsing response")
		}
		json.Unmarshal(data, &returnedKeys)
		return returnedKeys, nil
	}

	log.Info().Msgf("Bad Response from server %v", resp)
	readBody, _ := ioutil.ReadAll(resp.Body)

	log.Info().Msgf("Bad Response from server body is %s", string(readBody))

	return nil, errors.New("Key Not Found")
}

type LocalActivationKeyService struct {
	db DynamoConnection
}

func NewLocalActivationKeyService(db DynamoConnection) ActivationKeyService {
	return &LocalActivationKeyService{db}
}

func (aks *LocalActivationKeyService) GetActivationKeyFromCodes(ctx context.Context, activationKey string) (returnedKeys *ActivationKeys, returnedErr error) {

	result, err := aks.db.Client.Query(ctx, &dynamodb.QueryInput{
		TableName:              aws.String(aks.db.prefixedTableName(CodesTableName)),
		KeyConditionExpression: aws.String("code = :a"),
		ExpressionAttributeValues: map[string]types.AttributeValue{
			":a": &types.AttributeValueMemberS{Value: activationKey},
		},
	})
	if err != nil {
		log.Error().Err(err).Msg("error whilst searching for userId")
	}

	if result.Count > 0 {
		err = attributevalue.UnmarshalListOfMaps(result.Items, &returnedKeys)
		if err != nil {
			log.Error().Err(err).Msg("unable to convert dynamo result into ActorUser")
		}

		return returnedKeys, nil
	}

	return nil, errors.New("Not Yet Implemented")
}
