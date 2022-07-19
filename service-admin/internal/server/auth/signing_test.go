package auth_test

import (
	"bytes"
	"context"
	"crypto/ecdsa"
	"fmt"
	"io"
	"net/http"
	"sync"
	"testing"

	"github.com/golang-jwt/jwt/v4"
	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/auth"
	"github.com/stretchr/testify/assert"
)

type mockHTTPClient struct {
	retryCount int
	doFunc     func(r *http.Request, count int) (*http.Response, error)
}

func (m *mockHTTPClient) Do(request *http.Request) (*http.Response, error) {
	response, err := m.doFunc(request, m.retryCount)
	m.retryCount--

	return response, err
}

func TestSigningKey_Fetch(t *testing.T) {
	t.Parallel()

	keyURL := "https://my.key.url"
	goodKeyPem := "-----BEGIN PUBLIC KEY-----\n" +
		"MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE14egxCIz1we6nnObHgEZz7118DSc\n" +
		"4a5xtYe3//1dRKlEsp06c4IdnvUNEiklCK4plVrc/jbl6LN2mAETULYoig==\n" +
		"-----END PUBLIC KEY-----"
	goodKey, _ := jwt.ParseECPublicKeyFromPEM([]byte(goodKeyPem))
	keyCache := &sync.Map{}
	keyCache.Store(keyURL+"/test", goodKey)

	type args struct {
		ctx   context.Context
		keyID string
	}

	tests := []struct {
		name           string
		args           args
		requestFunc    func(r *http.Request, count int) (*http.Response, error)
		requestRetries int
		requestCache   *sync.Map
		want           *ecdsa.PublicKey
		wantErr        assert.ErrorAssertionFunc
	}{
		{
			name: "it fetches a valid key",
			args: args{
				ctx:   context.Background(),
				keyID: "test",
			},
			requestFunc: func(r *http.Request, count int) (*http.Response, error) {
				return &http.Response{
					StatusCode: 200,
					Body:       io.NopCloser(bytes.NewReader([]byte(goodKeyPem))),
				}, nil
			},
			requestCache: &sync.Map{},
			want:         goodKey,
			wantErr:      assert.NoError,
		},
		{
			name: "it fetches a valid key from the cache",
			args: args{
				ctx:   context.Background(),
				keyID: "test",
			},
			requestFunc: func(r *http.Request, count int) (*http.Response, error) {
				t.Error("handler should not be called")
				return nil, nil
			},
			requestCache: keyCache,
			want:         goodKey,
			wantErr:      assert.NoError,
		},
		{
			name: "it retries a failed fetch until it succeeds",
			args: args{
				ctx:   context.Background(),
				keyID: "test",
			},
			requestFunc: func(r *http.Request, count int) (*http.Response, error) {
				switch count {
				case 0:
					// success
					return &http.Response{
						StatusCode: 200,
						Body:       io.NopCloser(bytes.NewReader([]byte(goodKeyPem))),
					}, nil
				case 1:
					// issue with underlying connection
					return &http.Response{}, http.ErrServerClosed
				default:
					// 404 response
					return &http.Response{
						StatusCode: 404,
						Body:       io.NopCloser(bytes.NewReader([]byte(""))),
					}, nil
				}
			},
			requestCache:   &sync.Map{},
			requestRetries: 2,
			want:           goodKey,
			wantErr:        assert.NoError,
		},
	}

	for _, tt := range tests { //nolint:paralleltest
		tt := tt

		t.Run(tt.name, func(t *testing.T) {
			retries := 0
			if tt.requestRetries != 0 {
				retries = tt.requestRetries
			}

			Client = &mockHTTPClient{
				retryCount: retries,
				doFunc:     tt.requestFunc,
			}
			PublicKeyCache = tt.requestCache

			k := &SigningKey{
				PublicKeyURL: keyURL,
			}

			got, err := k.Fetch(tt.args.ctx, tt.args.keyID)
			if !tt.wantErr(t, err, fmt.Sprintf("Fetch(%v, %v)", tt.args.ctx, tt.args.keyID)) {
				return
			}
			assert.Equalf(t, tt.want, got, "Fetch(%v, %v)", tt.args.ctx, tt.args.keyID)
		})
	}
}
