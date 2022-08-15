package data

import (
	"context"
	"errors"
	"io"
	"net/http"
	"reflect"
	"strings"
	"testing"
	"time"

	"github.com/aws/aws-sdk-go-v2/aws"
	v4 "github.com/aws/aws-sdk-go-v2/aws/signer/v4"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/aws/aws-sdk-go-v2/service/dynamodb/types"
)

type mockSigner struct {
	SignHTTPFunc func(context.Context, aws.Credentials, *http.Request, string, string, string, time.Time, ...func(options *v4.SignerOptions)) error
}

func (ms *mockSigner) SignHTTP(ctx context.Context, cred aws.Credentials, req *http.Request, sha string, api string, region string, time time.Time, opt ...func(options *v4.SignerOptions)) error {
	if ms.SignHTTPFunc != nil {
		return ms.SignHTTPFunc(ctx, cred, req, sha, api, region, time, opt...)
	}

	return nil
}

type mockHTTPClient struct {
	DoFunc func(*http.Request) (*http.Response, error)
}

func (mc *mockHTTPClient) Do(rq *http.Request) (*http.Response, error) {
	if mc.DoFunc != nil {
		return mc.DoFunc(rq)
	}

	jsonStr := `[{"active":false,"actor":"700000000111","code":"WWFCCH41R123","dob":"20-06-1995","expiry_date":1672568225,"generated_date":"1-1-2022","last_updated_date":"6-6-2022","lpa":"700000000138","status_details":"Revoked"}]`
	jsonStrReader := strings.NewReader(jsonStr)

	return &http.Response{StatusCode: 200, Body: io.NopCloser(jsonStrReader)}, nil
}

type mockDynamoDBClient struct {
	QueryFunc   func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)
	GetItemFunc func(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error)
}

func (m *mockDynamoDBClient) Query(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
	if m.QueryFunc != nil {
		return m.QueryFunc(ctx, params, optFns...)
	}

	return nil, nil
}

func (m *mockDynamoDBClient) GetItem(ctx context.Context, params *dynamodb.GetItemInput, optFns ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error) {
	return nil, nil
}

func TestOnlineActivationKeyService_GetActivationKeyFromCodes(t *testing.T) {
	t.Parallel()

	type fields struct {
		awsSigner      Signer
		credentials    aws.Credentials
		codesAPIURL    string
		mockHTTPClient *mockHTTPClient
	}

	type args struct {
		ctx           context.Context
		activationKey string
	}

	tests := []struct {
		name             string
		fields           fields
		args             args
		wantReturnedKeys *[]ActivationKey
		wantErr          bool
	}{
		{
			name: "test online activation key service",
			fields: fields{
				awsSigner:      &mockSigner{},
				credentials:    aws.Credentials{},
				codesAPIURL:    "",
				mockHTTPClient: &mockHTTPClient{},
			},
			args: args{
				ctx:           context.Background(),
				activationKey: "WWFCCH41R123",
			},
			wantReturnedKeys: &[]ActivationKey{
				{
					Active:          false,
					Actor:           "700000000111",
					Code:            "WWFCCH41R123",
					Dob:             "20-06-1995",
					ExpiryDate:      1672568225,
					GeneratedDate:   "1-1-2022",
					LastUpdatedDate: "6-6-2022",
					Lpa:             "700000000138",
					StatusDetails:   "Revoked",
				},
			},
			wantErr: false,
		},
		{
			name: "test error when signer fails",
			fields: fields{
				awsSigner: &mockSigner{SignHTTPFunc: func(ctx context.Context, c aws.Credentials, r *http.Request, s1, s2, s3 string, t time.Time, f ...func(options *v4.SignerOptions)) error {
					return errors.New("Some Signing Error")
				}},
				credentials:    aws.Credentials{},
				codesAPIURL:    "",
				mockHTTPClient: &mockHTTPClient{},
			},
			args: args{
				ctx:           context.Background(),
				activationKey: "WWFCCH41R123",
			},
			wantErr: true,
		},
		{
			name: "test error when http client fails",
			fields: fields{
				awsSigner:   &mockSigner{},
				credentials: aws.Credentials{},
				codesAPIURL: "",
				mockHTTPClient: &mockHTTPClient{DoFunc: func(r *http.Request) (*http.Response, error) {
					return nil, errors.New("Client Do Error")
				}},
			},
			args: args{
				ctx:           context.Background(),
				activationKey: "WWFCCH41R123",
			},
			wantErr: true,
		},
		{
			name: "test online activation key service error on status code other than 200",
			fields: fields{
				awsSigner:   &mockSigner{},
				credentials: aws.Credentials{},
				codesAPIURL: "",
				mockHTTPClient: &mockHTTPClient{DoFunc: func(r *http.Request) (*http.Response, error) {
					return &http.Response{StatusCode: 404}, nil
				}},
			},
			args: args{
				ctx:           context.Background(),
				activationKey: "WWFCCH41R123",
			},
			wantErr: true,
		},
		{
			name: "error with malformed json",
			fields: fields{
				awsSigner:   &mockSigner{},
				credentials: aws.Credentials{},
				codesAPIURL: "",
				mockHTTPClient: &mockHTTPClient{DoFunc: func(r *http.Request) (*http.Response, error) {
					jsonStrReader := strings.NewReader("jsonStr")
					return &http.Response{StatusCode: 200, Body: io.NopCloser(jsonStrReader)}, nil
				}},
			},
			args: args{
				ctx:           context.Background(),
				activationKey: "WWFCCH41R123",
			},
			wantErr: true,
		},
		{
			name: "error no response body",
			fields: fields{
				awsSigner:   &mockSigner{},
				credentials: aws.Credentials{},
				codesAPIURL: "",
				mockHTTPClient: &mockHTTPClient{DoFunc: func(r *http.Request) (*http.Response, error) {
					return &http.Response{StatusCode: 200}, nil
				}},
			},
			args: args{
				ctx:           context.Background(),
				activationKey: "WWFCCH41R123",
			},
			wantErr: true,
		},
	}

	for _, tt := range tests {
		tt := tt
		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			aks := &OnlineActivationKeyService{
				awsSigner:   tt.fields.awsSigner,
				credentials: tt.fields.credentials,
				codesAPIURL: tt.fields.codesAPIURL,
				httpClient:  tt.fields.mockHTTPClient,
			}
			gotReturnedKeys, err := aks.GetActivationKeyFromCodes(tt.args.ctx, tt.args.activationKey)
			if (err != nil) != tt.wantErr {
				t.Errorf("OnlineActivationKeyService.GetActivationKeyFromCodes() error = %v, wantErr %v", err, tt.wantErr)
				return
			}
			if !reflect.DeepEqual(gotReturnedKeys, tt.wantReturnedKeys) {
				t.Errorf("OnlineActivationKeyService.GetActivationKeyFromCodes() = %v, want %v", gotReturnedKeys, tt.wantReturnedKeys)
			}
		})
	}
}

func TestLocalActivationKeyService_GetActivationKeyFromCodes(t *testing.T) {
	t.Parallel()

	type fields struct {
		db DynamoConnection
	}

	type args struct {
		ctx           context.Context
		activationKey string
	}

	tests := []struct {
		name             string
		fields           fields
		args             args
		wantReturnedKeys *[]ActivationKey
		wantErr          bool
	}{
		{
			name: "test local codes connection",
			fields: fields{
				db: DynamoConnection{
					Prefix: "",
					Client: &mockDynamoDBClient{
						QueryFunc: func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
							return &dynamodb.QueryOutput{
									Count: 1,
									Items: []map[string]types.AttributeValue{
										{
											"active":            &types.AttributeValueMemberBOOL{Value: false},
											"actor":             &types.AttributeValueMemberS{Value: "700000000123"},
											"code":              &types.AttributeValueMemberS{Value: "WWFCCH41R123"},
											"dob":               &types.AttributeValueMemberS{Value: "20-06-1995"},
											"expiry_date":       &types.AttributeValueMemberN{Value: "1761157543"},
											"generated_date":    &types.AttributeValueMemberS{Value: "01-01-2022"},
											"last_updated_date": &types.AttributeValueMemberS{Value: "01-06-2022"},
											"lpa":               &types.AttributeValueMemberS{Value: "700000000138"},
											"status_details":    &types.AttributeValueMemberS{Value: "Revoked"},
										},
									},
								},
								nil
						},
					},
				},
			},
			args: args{
				ctx:           context.Background(),
				activationKey: "WWFCCH41R123",
			},
			wantReturnedKeys: &[]ActivationKey{
				{
					Active:          false,
					Actor:           "700000000123",
					Code:            "WWFCCH41R123",
					Dob:             "20-06-1995",
					ExpiryDate:      1761157543,
					GeneratedDate:   "01-01-2022",
					LastUpdatedDate: "01-06-2022",
					Lpa:             "700000000138",
					StatusDetails:   "Revoked",
				},
			},
			wantErr: false,
		},
		{
			name: "test local codes connection fails when not key not found",
			fields: fields{
				db: DynamoConnection{
					Prefix: "",
					Client: &mockDynamoDBClient{
						QueryFunc: func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
							return nil, errors.New("Fails for test")
						},
					},
				},
			},
			args: args{
				ctx:           context.Background(),
				activationKey: "WWFCCH41R123",
			},
			wantErr: true,
		},
		{
			name: "test local codes connection fails when invalid response recieved",
			fields: fields{
				db: DynamoConnection{
					Prefix: "",
					Client: &mockDynamoDBClient{
						QueryFunc: func(ctx context.Context, params *dynamodb.QueryInput, optFns ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
							return &dynamodb.QueryOutput{
									Count: 1,
									Items: []map[string]types.AttributeValue{
										{
											"active":            &types.AttributeValueMemberBOOL{Value: false},
											"actor":             &types.AttributeValueMemberS{Value: "700000000123"},
											"code":              &types.AttributeValueMemberS{Value: "WWFCCH41R123"},
											"dob":               &types.AttributeValueMemberS{Value: "20-06-1995"},
											"expiry_date":       &types.AttributeValueMemberN{Value: "1761157543"},
											"generated_date":    &types.AttributeValueMemberS{Value: "01-01-2022"},
											"last_updated_date": &types.AttributeValueMemberS{Value: "01-06-2022"},
											"lpa":               &types.AttributeValueMemberBOOL{Value: true},
											"status_details":    &types.AttributeValueMemberS{Value: "Revoked"},
										},
									},
								},
								nil
						},
					},
				},
			},
			args: args{
				ctx:           context.Background(),
				activationKey: "WWFCCH41R123",
			},
			wantErr: true,
		},
	}
	for _, tt := range tests {
		tt := tt
		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()
			aks := NewLocalActivationKeyService(tt.fields.db)
			gotReturnedKeys, err := aks.GetActivationKeyFromCodes(tt.args.ctx, tt.args.activationKey)
			if (err != nil) != tt.wantErr {
				t.Errorf("LocalActivationKeyService.GetActivationKeyFromCodes() error = %v, wantErr %v", err, tt.wantErr)
				return
			}
			if !reflect.DeepEqual(gotReturnedKeys, tt.wantReturnedKeys) {
				t.Errorf("LocalActivationKeyService.GetActivationKeyFromCodes() = %v, want %v", gotReturnedKeys, tt.wantReturnedKeys)
			}
		})
	}
}
