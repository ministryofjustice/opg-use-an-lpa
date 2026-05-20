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
	"time"

	"github.com/aws/aws-sdk-go-v2/aws"
	v4 "github.com/aws/aws-sdk-go-v2/aws/signer/v4"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/aws/aws-sdk-go-v2/feature/dynamodb/attributevalue"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
	"github.com/aws/aws-sdk-go-v2/service/secretsmanager"
	"github.com/golang-jwt/jwt/v5"
)

const (
	// opg-use-an-lpa+test-user@digital.justice.gov.uk
	userID = "bf9e7e77-f283-49c6-a79c-65d5d309ef77"
)

func main() {
	var (
		ctx = context.Background()

		lpaStoreBaseURL                = os.Getenv("LPA_STORE_BASE_URL")
		lpaStoreSecretARN              = os.Getenv("LPA_STORE_SECRET_ARN")
		activationCodesTableARN        = os.Getenv("ACTIVATION_CODES_TABLE_ARN")
		paperVerificationCodesTableARN = os.Getenv("PAPER_VERIFICATION_CODES_TABLE_ARN")
		userLpaActorMapTableARN        = os.Getenv("USER_LPA_ACTOR_MAP_TABLE_ARN")
		viewerCodesTableARN            = os.Getenv("VIEWER_CODES_TABLE_ARN")
	)

	lpas := []Lpa{
		readLpa("M-7890-0400-4000", "d9b9caa0-d657-4917-b15a-462f8acd32bf"),
		readLpa("M-7890-0500-5009", "626458b6-eb4e-21e4-95db-ce9b5b3e3f7c"),
	}

	if lpaStoreBaseURL != "" && lpaStoreSecretARN != "" {
		if err := runLpaStore(ctx, lpaStoreBaseURL, lpaStoreSecretARN, lpas); err != nil {
			log.Fatal(err)
		}
	} else if activationCodesTableARN != "" && paperVerificationCodesTableARN != "" {
		if err := runCodes(ctx, activationCodesTableARN, paperVerificationCodesTableARN, lpas); err != nil {
			log.Fatal(err)
		}
	} else if userLpaActorMapTableARN != "" && viewerCodesTableARN != "" {
		if err := runViewerCodes(ctx, userLpaActorMapTableARN, viewerCodesTableARN, lpas); err != nil {
			log.Fatal(err)
		}
	} else {
		log.Fatal(`for seeding lpa-store set LPA_STORE_BASE_URL and LPA_STORE_SECRET_ARN
for seeding activation and paper verification codes set ACTIVATION_CODES_TABLE_ARN and PAPER_VERIFICATION_CODES_TABLE
for seeding viewer codes set USER_LPA_ACTOR_MAP_TABLE_ARN and VIEWER_CODES_TABLE_ARN`)
	}
}

func runLpaStore(ctx context.Context, lpaStoreBaseURL, lpaStoreSecretARN string, lpas []Lpa) error {
	cfg, err := config.LoadDefaultConfig(ctx)
	if err != nil {
		return fmt.Errorf("load config: %w", err)
	}

	lpaStore, err := newLpaStoreClient(ctx, cfg, lpaStoreBaseURL, lpaStoreSecretARN)
	if err != nil {
		return fmt.Errorf("new lpa store client: %w", err)
	}

	for _, lpa := range lpas {
		created, err := lpaStore.createLPA(ctx, lpa.UID, lpa.Body)
		if err != nil {
			return fmt.Errorf("create %s: %w", lpa.UID, err)
		}

		if created {
			if err := lpaStore.update(ctx, lpa.UID, map[string]any{
				"type": "CERTIFICATE_PROVIDER_SIGN",
				"changes": []map[string]any{
					{"key": "/certificateProvider/signedAt", "new": "2024-01-02T11:00:00Z", "old": nil},
				},
			}); err != nil {
				return fmt.Errorf("certificate provider sign: %w", err)
			}

			if err := lpaStore.update(ctx, lpa.UID, map[string]any{
				"type": "ATTORNEY_SIGN",
				"changes": []map[string]any{
					{"key": "/attorneys/0/signedAt", "new": "2024-01-02T11:00:00Z", "old": nil},
				},
			}); err != nil {
				return fmt.Errorf("attorney sign: %w", err)
			}

			if err := lpaStore.update(ctx, lpa.UID, map[string]any{
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

			if err := lpaStore.update(ctx, lpa.UID, map[string]any{"type": "STATUTORY_WAITING_PERIOD"}); err != nil {
				return fmt.Errorf("statutory waiting period: %w", err)
			}

			if err := lpaStore.update(ctx, lpa.UID, map[string]any{"type": "REGISTER"}); err != nil {
				return fmt.Errorf("register: %w", err)
			}
		}
	}

	return nil
}

type activationCode struct {
	Code            string `dynamodbav:"code"`
	Active          bool   `dynamodbav:"active"`
	Actor           string `dynamodbav:"actor"`
	Dob             string `dynamodbav:"dob"`
	ExpiryDate      int64  `dynamodbav:"expiry_date"`
	GeneratedDate   string `dynamodbav:"generated_date"`
	LastUpdatedDate string `dynamodbav:"last_updated_date"`
	Lpa             string `dynamodbav:"lpa"`
	StatusDetails   string `dynamodbav:"status_details"`
}

type paperVerificationCode struct {
	PK           string
	UpdatedAt    time.Time
	ActorLPA     string
	ExpiresAt    time.Time `dynamodbav:",omitempty"`
	ExpiryReason string    `dynamodbav:",omitempty"`
}

func runCodes(ctx context.Context, activationCodesTableARN, paperVerificationCodesTableARN string, lpas []Lpa) error {
	cfg, err := config.LoadDefaultConfig(ctx)
	if err != nil {
		return fmt.Errorf("load config: %w", err)
	}

	dynamo := dynamodb.NewFromConfig(cfg)

	_, err = dynamo.BatchWriteItem(ctx, createBatch(map[string][]any{
		activationCodesTableARN: {
			activationCode{
				Code:            "PAPERATORNEY",
				Active:          true,
				Actor:           lpas[0].AttorneyUIDs[0],
				Dob:             "1982-07-24",
				ExpiryDate:      time.Now().AddDate(1, 0, 0).Unix(),
				GeneratedDate:   time.Now().Format(time.DateOnly),
				LastUpdatedDate: time.Now().Format(time.DateOnly),
				Lpa:             lpas[0].UID,
				StatusDetails:   "Generated",
			},
		},
		paperVerificationCodesTableARN: {
			paperVerificationCode{
				PK:        "PAPER#P-1234-1234-1234-12",
				UpdatedAt: time.Now(),
				ActorLPA:  lpas[0].DonorUID + "#" + lpas[0].UID,
			},
			paperVerificationCode{
				PK:        "PAPER#P-1234-1234-1234-23",
				UpdatedAt: time.Now(),
				ActorLPA:  lpas[0].AttorneyUIDs[0] + "#" + lpas[0].UID,
			},
			paperVerificationCode{
				PK:        "PAPER#P-1234-1234-1234-34",
				UpdatedAt: time.Now(),
				ActorLPA:  lpas[0].TrustCorporationUIDs[0] + "#" + lpas[0].UID,
			},
			paperVerificationCode{
				PK:           "PAPER#P-3456-3456-3456-34",
				UpdatedAt:    time.Now(),
				ActorLPA:     lpas[0].DonorUID + "#" + lpas[0].UID,
				ExpiresAt:    time.Now(),
				ExpiryReason: "cancelled",
			},
			paperVerificationCode{
				PK:           "PAPER#P-5678-5678-5678-56",
				UpdatedAt:    time.Now(),
				ActorLPA:     lpas[0].DonorUID + "#" + lpas[0].UID,
				ExpiresAt:    time.Now(),
				ExpiryReason: "first_time_use",
			},
			paperVerificationCode{
				PK:           "PAPER#P-5678-5678-5678-67",
				UpdatedAt:    time.Now(),
				ActorLPA:     lpas[0].DonorUID + "#" + lpas[0].UID,
				ExpiresAt:    time.Now().Add(7 * 24 * time.Hour),
				ExpiryReason: "first_time_use",
			},
		},
	}))

	return err
}

type userLpaActorMap struct {
	Id          string
	LpaUid      string
	ActorId     string
	Added       time.Time
	ActivatedOn time.Time
	UserId      string
	Comment     string
}

type viewerCode struct {
	ViewerCode   string
	SiriusUid    string
	Expires      string
	Added        time.Time
	Organisation string
	UserLpaActor string
	Comment      string
}

func runViewerCodes(ctx context.Context, userLpaActorMapTableARN, viewerCodesTableARN string, lpas []Lpa) error {
	cfg, err := config.LoadDefaultConfig(ctx)
	if err != nil {
		return fmt.Errorf("load config: %w", err)
	}

	dynamo := dynamodb.NewFromConfig(cfg)

	nextWeek := time.Now().AddDate(0, 0, 7).Format(time.RFC3339)
	lastWeek := time.Now().AddDate(0, 0, -7).Format(time.RFC3339)

	_, err = dynamo.BatchWriteItem(ctx, createBatch(map[string][]any{
		userLpaActorMapTableARN: {
			userLpaActorMap{
				Id:          lpas[0].UserLpaActorMapID,
				LpaUid:      lpas[0].UID,
				ActorId:     lpas[0].DonorUID,
				Added:       time.Now(),
				ActivatedOn: time.Now(),
				UserId:      userID,
				Comment:     "Seeded data",
			},
			userLpaActorMap{
				Id:          lpas[1].UserLpaActorMapID,
				LpaUid:      lpas[1].UID,
				ActorId:     lpas[1].DonorUID,
				Added:       time.Now(),
				ActivatedOn: time.Now(),
				UserId:      userID,
				Comment:     "Seeded data",
			},
		},
		viewerCodesTableARN: {
			viewerCode{
				ViewerCode:   "A000B000C000",
				SiriusUid:    lpas[0].UID,
				Expires:      nextWeek,
				Added:        time.Now(),
				Organisation: "Test Organisation",
				UserLpaActor: lpas[0].UserLpaActorMapID,
				Comment:      "Seeded data: Valid viewer code",
			},
			viewerCode{
				ViewerCode:   "E000F000G000",
				SiriusUid:    lpas[1].UID,
				Expires:      lastWeek,
				Added:        time.Now(),
				Organisation: "Another Test Organisation",
				UserLpaActor: lpas[1].UserLpaActorMapID,
				Comment:      "Seeded data: Valid viewer code",
			},
		},
	}))
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

func (l *lpaStoreClient) createLPA(ctx context.Context, uid string, body []byte) (created bool, err error) {
	resp, err := l.do(ctx, http.MethodPut, "/lpas/"+uid, bytes.NewReader(body))
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

func (l *lpaStoreClient) update(ctx context.Context, lpaUID string, update map[string]any) error {
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

func createBatch(req map[string][]any) *dynamodb.BatchWriteItemInput {
	input := &dynamodb.BatchWriteItemInput{
		RequestItems: map[string][]types.WriteRequest{},
	}

	for table, items := range req {
		for _, item := range items {
			encoded, _ := attributevalue.MarshalMap(item)

			input.RequestItems[table] = append(input.RequestItems[table], types.WriteRequest{
				PutRequest: &types.PutRequest{Item: encoded},
			})
		}
	}

	return input
}

type Lpa struct {
	UID                  string
	Body                 []byte
	DonorUID             string
	AttorneyUIDs         []string
	TrustCorporationUIDs []string

	// associate the ID with the LPA so it is set consistently across runs
	UserLpaActorMapID string
}

func readLpa(uid, userLpaActorMapID string) Lpa {
	body, _ := os.ReadFile("lpas/" + uid + ".json")

	var lpa struct {
		Donor struct {
			UID string
		}
		Attorneys []struct {
			UID string
		}
		TrustCorporations []struct {
			UID string
		}
	}
	json.Unmarshal(body, &lpa)

	var attorneyUIDs []string
	for _, a := range lpa.Attorneys {
		attorneyUIDs = append(attorneyUIDs, a.UID)
	}

	var trustCorporationUIDs []string
	for _, c := range lpa.TrustCorporations {
		trustCorporationUIDs = append(trustCorporationUIDs, c.UID)
	}

	return Lpa{
		UID:                  uid,
		Body:                 body,
		DonorUID:             lpa.Donor.UID,
		AttorneyUIDs:         attorneyUIDs,
		TrustCorporationUIDs: trustCorporationUIDs,
		UserLpaActorMapID:    userLpaActorMapID,
	}
}
