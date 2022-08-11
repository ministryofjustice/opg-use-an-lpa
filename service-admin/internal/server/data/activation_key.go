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

var ErrActivationKeyNotFound error = errors.New("ActivationKeyNotFound")

type ActivationKeyService interface {
	GetActivationKeyFromCodes(context.Context, string) (*[]ActivationKey, error)
}

type ActivationKey struct {
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

func (aks *OnlineActivationKeyService) GetActivationKeyFromCodes(ctx context.Context, activationKey string) (returnedKeys *[]ActivationKey, returnedErr error) {
	jsonStr := []byte(fmt.Sprintf(`{"code":"%s"}`, activationKey))

	r, err := http.NewRequest(http.MethodPost, aks.codesAPIURL, bytes.NewBuffer(jsonStr))
	if err != nil {
		log.Error().AnErr("Could not create request in GetActivationKeyFromCodes", err)
		return nil, ErrActivationKeyNotFound
	}

	r.Header.Set("Content-Type", "application/json")

	//calculate hash of request body
	hasher := sha256.New()
	hasher.Write(jsonStr)
	shaHash := hex.EncodeToString(hasher.Sum(nil))

	err = aks.awsSigner.SignHTTP(ctx, aks.credentials, r, shaHash, "execute-api", "eu-west-1", time.Now())

	if err != nil {
		log.Error().AnErr("Error Signing request for activation key %v", err)
		return nil, ErrActivationKeyNotFound
	}

	client := http.Client{}

	resp, err := client.Do(r)
	if err != nil {
		log.Error().AnErr("Client could not send request when requesting activation key", err)
		return nil, ErrActivationKeyNotFound
	}

	if resp.StatusCode == 200 {
		data, err := ioutil.ReadAll(resp.Body)
		if err != nil {
			log.Error().AnErr("Error parsing 200 response from codes service", err)

			return nil, ErrActivationKeyNotFound
		}

		err = json.Unmarshal(data, &returnedKeys)
		if err != nil {
			log.Error().AnErr("Error unmarshalling json from code service", err)
			return nil, ErrActivationKeyNotFound
		}

		return returnedKeys, nil
	}

	log.Error().Msgf("Bad Response from server when requesting activation key %v", resp)

	return nil, ErrActivationKeyNotFound
}

//Connect to Local implementation of codes service

type LocalActivationKeyService struct {
	db DynamoConnection
}

func NewLocalActivationKeyService(db DynamoConnection) ActivationKeyService {
	return &LocalActivationKeyService{db}
}

func (aks *LocalActivationKeyService) GetActivationKeyFromCodes(ctx context.Context, activationKey string) (returnedKeys *[]ActivationKey, returnedErr error) {
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

	return nil, ErrActivationKeyNotFound
}
