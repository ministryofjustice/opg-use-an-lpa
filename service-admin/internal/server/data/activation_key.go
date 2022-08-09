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
	"github.com/rs/zerolog/log"
)

type ActivationKeyService struct {
	awsSigner   *v4.Signer
	credentials aws.Credentials
	codesAPIURL string
}

func NewActivationKeyService(awsSigner *v4.Signer, credentials aws.Credentials, codesAPIURL string) *ActivationKeyService {
	return &ActivationKeyService{awsSigner: awsSigner, credentials: credentials, codesAPIURL: codesAPIURL}
}

// example:
//         active: true
//         code: "YsSu4iAztUXm"
//         last_updated_date: 2022-08-20
//         status_details: "Generated"
//         expiry_date: 2023-08-20
//         dob: 1983-08-20
//         generated_date: 2022-08-20
//         lpa: "eed4f597-fd87-4536-99d0-895778824861"
//         actor: "12ad81a9-f89d-4804-99f5-7c0c8669ac9b"

type ActivationKeys []struct {
	Active          bool   `json:"active"`
	Actor           string `json:"actor"`
	Code            string `json:"code"`
	Dob             string `json:"dob"`
	ExpiryDate      int    `json:"expiry_date"`
	GeneratedDate   string `json:"generated_date"`
	LastUpdatedDate string `json:"last_updated_date"`
	Lpa             string `json:"lpa"`
	StatusDetails   string `json:"status_details"`
}

func (aks *ActivationKeyService) GetActivationKeyFromCodesEndpoint(ctx context.Context, activationKey string) (returnedKeys *ActivationKeys, returnedErr error) {

	jsonStr := []byte(fmt.Sprintf(`{"code":"%s"}`, activationKey))

	r, err := http.NewRequest(http.MethodPost, aks.codesAPIURL, bytes.NewBuffer(jsonStr))
	print(string(jsonStr))

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
