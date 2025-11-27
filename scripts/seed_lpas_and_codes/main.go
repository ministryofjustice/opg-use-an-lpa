package main

import (
	"bytes"
	"context"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"os"
	"strings"
	"time"

	"github.com/aws/aws-sdk-go-v2/aws"
	v4 "github.com/aws/aws-sdk-go-v2/aws/signer/v4"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/aws/aws-sdk-go-v2/service/secretsmanager"
	"github.com/golang-jwt/jwt/v5"
)

const (
	lpaUID              = "M-7890-0400-4000"
	donorUID            = "eda719db-8880-4dda-8c5d-bb9ea12c236f"
	attorneyUID         = "9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d"
	trustCorporationUID = "1d95993a-ffbb-484c-b2fe-f4cca51801da"
	lpaBody             = `{
	"lpaType": "personal-welfare",
	"channel": "paper",
	"language": "en",
	"donor": {
		"uid": "` + donorUID + `",
		"firstNames": "Feeg",
		"lastName": "Bundlaaaa",
		"address": {
			"line1": "74 Cloob Close",
			"town": "Mahhhhhhhhhh",
			"postcode": "TP6 8EX",
			"country": "GB"
		},
		"dateOfBirth": "1970-01-24",
		"email": "nobody@not.a.real.domain",
		"contactLanguagePreference": "en"
	},
	"attorneys": [
		{
			"uid": "` + attorneyUID + `",
			"firstNames": "Herman",
			"lastName": "Seakrest",
			"address": {
				"line1": "81 NighOnTimeWeBuiltIt Street",
				"town": "Mahhhhhhhhhh",
				"postcode": "PC4 6UZ",
				"country": "GB"
			},
			"dateOfBirth": "1982-07-24",
			"status": "active",
			"appointmentType": "original",
			"channel": "paper"
		}
	],
	"trustCorporations": [
		{
			"uid": "` + trustCorporationUID + `",
			"name": "Trust us Corp.",
			"address": {
				"line1": "103 Line 1",
				"town": "Town",
				"country": "GB"
			},
			"status": "active",
			"appointmentType": "original",
			"channel": "paper",
			"companyNumber": "ABCD1234"
		}
	],
	"certificateProvider": {
		"uid": "6808960d-12cf-47c5-a2bc-3177deb8599c",
		"firstNames": "Vone",
		"lastName": "Spust",
		"address": {
			"line1": "122111 Zonnington Way",
			"town": "Mahhhhhhhhhh",
			"country": "GB"
		},
		"channel": "online",
		"email": "a@example.com",
		"phone": "070009000"
	},
	"lifeSustainingTreatmentOption": "option-a",
	"signedAt": "2024-01-10T23:00:00Z",
	"witnessedByCertificateProviderAt": "2024-01-11T01:00:00Z",
	"certificateProviderNotRelatedConfirmedAt": "2024-01-11T22:00:00Z",
	"howAttorneysMakeDecisions": "jointly",
	"restrictionsAndConditions": "I do not want to be put into a care home unless x"
}`
)

func main() {
	var (
		lpaStoreBaseURL   = os.Getenv("LPA_STORE_BASE_URL")
		lpaStoreSecretARN = os.Getenv("LPA_STORE_SECRET_ARN")
		codesTableARN     = os.Getenv("CODES_TABLE_ARN")
	)
	if (lpaStoreBaseURL == "" && lpaStoreSecretARN == "") == (codesTableARN == "") {
		log.Fatal("set LPA_STORE_BASE_URL and LPA_STORE_SECRET_ARN for lpa-store seeding, or CODES_TABLE_ARN for codes seeding")
	}

	if codesTableARN == "" {
		if err := runLpaStore(context.Background(), lpaStoreBaseURL, lpaStoreSecretARN); err != nil {
			log.Fatal(err)
		}
	} else {
		if err := runCodes(context.Background(), codesTableARN); err != nil {
			log.Fatal(err)
		}
	}
}

func runLpaStore(ctx context.Context, lpaStoreBaseURL, lpaStoreSecretARN string) error {
	cfg, err := config.LoadDefaultConfig(ctx)
	if err != nil {
		return fmt.Errorf("load config: %w", err)
	}

	lpaStore, err := newLpaStoreClient(ctx, cfg, lpaStoreBaseURL, lpaStoreSecretARN)
	if err != nil {
		return fmt.Errorf("new lpa store client: %w", err)
	}

	created, err := lpaStore.createLPA(ctx)
	if err != nil {
		return err
	}

	if created {
		if err := lpaStore.update(ctx, map[string]any{
			"type": "CERTIFICATE_PROVIDER_SIGN",
			"changes": []map[string]any{
				{"key": "/certificateProvider/signedAt", "new": "2024-01-02T11:00:00Z", "old": nil},
			},
		}); err != nil {
			return fmt.Errorf("certificate provider sign: %w", err)
		}

		if err := lpaStore.update(ctx, map[string]any{
			"type": "ATTORNEY_SIGN",
			"changes": []map[string]any{
				{"key": "/attorneys/0/signedAt", "new": "2024-01-02T11:00:00Z", "old": nil},
			},
		}); err != nil {
			return fmt.Errorf("attorney sign: %w", err)
		}

		if err := lpaStore.update(ctx, map[string]any{
			"type": "TRUST_CORPORATION_SIGN",
			"changes": []map[string]any{
				{"key": "/trustCorporations/0/contactLanguagePreference", "new": "en", "old": nil},
				{"key": "/trustCorporations/0/mobile", "new": "07000120202", "old": nil},
				{"key": "/trustCorporations/0/companyNumber", "new": "575656565", "old": "ABCD1234"},
				{"key": "/trustCorporations/0/signatories/0/firstNames", "new": "John", "old": nil},
				{"key": "/trustCorporations/0/signatories/0/lastName", "new": "Signer", "old": nil},
				{"key": "/trustCorporations/0/signatories/0/professionalTitle", "new": "Law guy", "old": nil},
				{"key": "/trustCorporations/0/signatories/0/signedAt", "new": "2024-01-02T11:00:00Z", "old": nil},
			},
		}); err != nil {
			return fmt.Errorf("trust corporation sign: %w", err)
		}

		if err := lpaStore.update(ctx, map[string]any{"type": "STATUTORY_WAITING_PERIOD"}); err != nil {
			return fmt.Errorf("statutory waiting period: %w", err)
		}

		if err := lpaStore.update(ctx, map[string]any{"type": "REGISTER"}); err != nil {
			return fmt.Errorf("register: %w", err)
		}
	}

	return nil
}

func runCodes(ctx context.Context, codesTableARN string) error {
	cfg, err := config.LoadDefaultConfig(ctx)
	if err != nil {
		return fmt.Errorf("load config: %w", err)
	}

	dynamo := dynamodb.NewFromConfig(cfg)

	_, err = dynamo.BatchWriteItem(ctx, &dynamodb.BatchWriteItemInput{
		RequestItems: map[string][]types.WriteRequest{
			codesTableARN: {
				{PutRequest: &types.PutRequest{
					Item: map[string]types.AttributeValue{
						"PK":        &types.AttributeValueMemberS{Value: "PAPER#P-1234-1234-1234-12"},
						"UpdatedAt": &types.AttributeValueMemberS{Value: time.Now().Format(time.RFC3339Nano)},
						"ActorLPA":  &types.AttributeValueMemberS{Value: donorUID + "#" + lpaUID},
					},
				}},
				{PutRequest: &types.PutRequest{
					Item: map[string]types.AttributeValue{
						"PK":        &types.AttributeValueMemberS{Value: "PAPER#P-1234-1234-1234-23"},
						"UpdatedAt": &types.AttributeValueMemberS{Value: time.Now().Format(time.RFC3339Nano)},
						"ActorLPA":  &types.AttributeValueMemberS{Value: attorneyUID + "#" + lpaUID},
					},
				}},
				{PutRequest: &types.PutRequest{
					Item: map[string]types.AttributeValue{
						"PK":        &types.AttributeValueMemberS{Value: "PAPER#P-1234-1234-1234-34"},
						"UpdatedAt": &types.AttributeValueMemberS{Value: time.Now().Format(time.RFC3339Nano)},
						"ActorLPA":  &types.AttributeValueMemberS{Value: trustCorporationUID + "#" + lpaUID},
					},
				}},
				{PutRequest: &types.PutRequest{
					Item: map[string]types.AttributeValue{
						"PK":           &types.AttributeValueMemberS{Value: "PAPER#P-3456-3456-3456-34"},
						"UpdatedAt":    &types.AttributeValueMemberS{Value: time.Now().Format(time.RFC3339Nano)},
						"ActorLPA":     &types.AttributeValueMemberS{Value: donorUID + "#" + lpaUID},
						"ExpiresAt":    &types.AttributeValueMemberS{Value: time.Now().Format(time.RFC3339Nano)},
						"ExpiryReason": &types.AttributeValueMemberS{Value: "cancelled"},
					},
				}},
				{PutRequest: &types.PutRequest{
					Item: map[string]types.AttributeValue{
						"PK":           &types.AttributeValueMemberS{Value: "PAPER#P-5678-5678-5678-56"},
						"UpdatedAt":    &types.AttributeValueMemberS{Value: time.Now().Format(time.RFC3339Nano)},
						"ActorLPA":     &types.AttributeValueMemberS{Value: donorUID + "#" + lpaUID},
						"ExpiresAt":    &types.AttributeValueMemberS{Value: time.Now().Format(time.RFC3339Nano)},
						"ExpiryReason": &types.AttributeValueMemberS{Value: "first_time_use"},
					},
				}},
				{PutRequest: &types.PutRequest{
					Item: map[string]types.AttributeValue{
						"PK":           &types.AttributeValueMemberS{Value: "PAPER#P-5678-5678-5678-67"},
						"UpdatedAt":    &types.AttributeValueMemberS{Value: time.Now().Format(time.RFC3339Nano)},
						"ActorLPA":     &types.AttributeValueMemberS{Value: donorUID + "#" + lpaUID},
						"ExpiresAt":    &types.AttributeValueMemberS{Value: time.Now().Add(7 * 24 * time.Hour).Format(time.RFC3339Nano)},
						"ExpiryReason": &types.AttributeValueMemberS{Value: "first_time_use"},
					},
				}},
			},
		},
	})
	return err
}

type lpaStoreClient struct {
	cfg     aws.Config
	baseURL string
	signer  *v4.Signer
	secret  []byte
}

func newLpaStoreClient(ctx context.Context, cfg aws.Config, baseURL, secretARN string) (*lpaStoreClient, error) {
	secretsClient := secretsmanager.NewFromConfig(cfg)

	secretKey, err := secretsClient.GetSecretValue(ctx, &secretsmanager.GetSecretValueInput{
		SecretId: aws.String(secretARN),
	})
	if err != nil {
		return nil, fmt.Errorf("retrieve secret: %w", err)
	}

	return &lpaStoreClient{
		cfg:     cfg,
		baseURL: baseURL,
		signer:  v4.NewSigner(),
		secret:  []byte(*secretKey.SecretString),
	}, nil
}

func (l *lpaStoreClient) createLPA(ctx context.Context) (created bool, err error) {
	resp, err := l.do(ctx, http.MethodPut, "/lpas/"+lpaUID, strings.NewReader(lpaBody))
	if err != nil {
		return false, fmt.Errorf("do lpa-store request: %w", err)
	}
	defer resp.Body.Close()

	switch resp.StatusCode {
	case http.StatusCreated:
		return true, nil

	case http.StatusBadRequest:
		body, _ := io.ReadAll(resp.Body)

		var error struct {
			Detail string `json:"detail"`
		}
		_ = json.Unmarshal(body, &error)

		if error.Detail == "LPA with UID already exists" {
			// ignore the error as the LPA we want is already there
			return false, nil
		}

		return false, fmt.Errorf("expected 201 response but got %d: %s", resp.StatusCode, body)

	default:
		body, _ := io.ReadAll(resp.Body)

		return false, fmt.Errorf("expected 201 response but got %d: %s", resp.StatusCode, body)
	}
}

func (l *lpaStoreClient) update(ctx context.Context, update map[string]any) error {
	data, err := json.Marshal(update)
	if err != nil {
		return err
	}

	resp, err := l.do(ctx, http.MethodPost, "/lpas/"+lpaUID+"/updates", bytes.NewReader(data))
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	switch resp.StatusCode {
	case http.StatusCreated:
		return nil

	case http.StatusNotFound:
		return fmt.Errorf("lpa was not found, did it fail to create?")

	default:
		body, _ := io.ReadAll(resp.Body)

		return fmt.Errorf("expected 201 response but got %d: %s", resp.StatusCode, body)
	}
}

func (l *lpaStoreClient) do(ctx context.Context, method, path string, body io.Reader) (*http.Response, error) {
	req, err := http.NewRequestWithContext(ctx, method, l.baseURL+path, body)
	if err != nil {
		return nil, fmt.Errorf("create lpa-store request: %w", err)
	}
	req.Header.Set("Content-Type", "application/json")

	token := jwt.NewWithClaims(jwt.SigningMethodHS256, jwt.RegisteredClaims{
		Issuer:   "opg.poas.use",
		IssuedAt: jwt.NewNumericDate(time.Now()),
		Subject:  "urn:opg:poas:use:users:00000000-0000-0000-0000-000000000000",
	})

	auth, err := token.SignedString(l.secret)
	if err != nil {
		return nil, fmt.Errorf("sign jwt: %w", err)
	}
	req.Header.Add("X-Jwt-Authorization", "Bearer "+auth)

	return callLambda(l.cfg, l.signer, req)
}

func callLambda(cfg aws.Config, signer *v4.Signer, req *http.Request) (*http.Response, error) {
	hash := sha256.New()

	if req.Body != nil {
		var reqBody bytes.Buffer

		if _, err := io.Copy(hash, io.TeeReader(req.Body, &reqBody)); err != nil {
			return nil, err
		}

		req.Body = io.NopCloser(&reqBody)
	}

	encodedBody := hex.EncodeToString(hash.Sum(nil))

	credentials, err := cfg.Credentials.Retrieve(req.Context())
	if err != nil {
		return nil, err
	}

	if err := signer.SignHTTP(req.Context(), credentials, req, encodedBody, "execute-api", cfg.Region, time.Now().UTC()); err != nil {
		return nil, err
	}

	return http.DefaultClient.Do(req)
}
