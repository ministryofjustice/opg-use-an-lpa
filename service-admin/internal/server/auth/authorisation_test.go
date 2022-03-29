package auth_test

import (
	"context"
	"errors"
	"net/http"
	"os"
	"testing"

	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/auth"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
	"github.com/stretchr/testify/assert"
)

type mockValidToken struct {
	Token
	validateFunc func(ctx context.Context, token string) (*Claims, error)
}

func (m *mockValidToken) Validate(ctx context.Context, token string) (*Claims, error) {
	return m.validateFunc(ctx, token)
}

// func testGoodKeyFunc() KeyFetchFunc {
// 	return func(ctx context.Context, keyURL string) (*ecdsa.PublicKey, error) {
// 		return jwt.ParseECPublicKeyFromPEM([]byte("-----BEGIN PUBLIC KEY-----" +
// 			"MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE14egxCIz1we6nnObHgEZz7118DSc" +
// 			"4a5xtYe3//1dRKlEsp06c4IdnvUNEiklCK4plVrc/jbl6LN2mAETULYoig==" +
// 			"-----END PUBLIC KEY-----"))
// 	}
// }

func TestMain(m *testing.M) {
	// nop the logger so panic and exit calls (if any) don't do anything.
	log.Logger = zerolog.Nop()

	os.Exit(m.Run())
}

func TestWithAuthorisation(t *testing.T) {
	t.Parallel()

	tests := []struct {
		name  string
		next  http.HandlerFunc
		token *mockValidToken
		want  int
	}{
		{
			name: "authenticates incoming valid request",
			next: http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
				i := r.Context().Value(handlers.UserContextKey{})
				c, is := i.(*Claims)
				if !is {
					t.Error("claims not added into request context")
				}

				assert.Equal(t, "Test", c.Name)
			}),
			token: &mockValidToken{
				validateFunc: func(ctx context.Context, token string) (*Claims, error) {
					return &Claims{
						Sub:   "1234",
						Name:  "Test",
						Email: "test@test.com",
					}, nil
				},
			},
			want: 200,
		},
		{
			name: "rejects bad request",
			next: http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
				t.Error("handler should not be called")
			}),
			token: &mockValidToken{
				validateFunc: func(ctx context.Context, token string) (*Claims, error) {
					return nil, errors.New("bad token supplied")
				},
			},
			want: 403,
		},
	}

	for _, tt := range tests {
		tt := tt

		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			h := WithAuthorisation(tt.next, tt.token)

			assert.HTTPStatusCode(t, h.ServeHTTP, "GET", "/", nil, tt.want)
		})
	}
}
