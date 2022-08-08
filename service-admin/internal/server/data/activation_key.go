package data

import (
	"bytes"
	"context"
	"crypto/sha256"
	"encoding/json"
	"errors"
	"io"
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

	codePayload := map[string]interface{}{
		"code": activationKey,
	}

	var buf bytes.Buffer

	err := json.NewEncoder(&buf).Encode(codePayload)
	if err != nil {
		return nil, errors.New("Cannot Encode Payload")
	}

	r, err := http.NewRequest(http.MethodPost, aks.codesAPIURL, &buf)

	//calculate hash of request body
	closer, err := r.GetBody()
	hasher := sha256.New()
	io.Copy(hasher, closer)
	shaHash := hasher.Sum(nil)

	aks.awsSigner.SignHTTP(ctx, aks.credentials, r, string(shaHash), "execute-api", "eu-west-1", time.Now())

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
	return nil, errors.New("Key Not Found")
}
