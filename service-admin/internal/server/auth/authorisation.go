package auth

import (
	"context"
	"net/http"

	"github.com/golang-jwt/jwt/v4"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/rs/zerolog/log"
)

type Claims struct {
	Sub   string `json:"sub"`
	Name  string `json:"name"`
	Email string `json:"email"`
}

func (c Claims) Valid() error {
	return nil
}

type tokenVerifier interface {
	Validate(ctx context.Context, token string) (*Claims, error)
}

type Token struct {
	SigningKey *SigningKey
}

func (t *Token) Validate(ctx context.Context, token string) (*Claims, error) {
	var claims = &Claims{}

	// Amazon use a non-standand JWT format that includes padding in the base64 values.
	jwt.DecodePaddingAllowed = true

	_, err := jwt.ParseWithClaims(token, claims, func(token *jwt.Token) (interface{}, error) {
		return t.SigningKey.Fetch(ctx, token.Header["kid"].(string))
	})
	if err != nil {
		return nil, err
	}

	return claims, nil
}

func WithAuthorisation(next http.Handler, token tokenVerifier) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		ctx := r.Context()

		claims, err := token.Validate(ctx, r.Header.Get("x-amzn-oidc-data"))
		if err != nil {
			log.Ctx(ctx).Err(err).Msg("failed to validate jwt")
			w.WriteHeader(http.StatusForbidden)
			return
		}

		log.Ctx(ctx).Info().Msgf("%s accessed the service", claims.Email)

		ctx = context.WithValue(ctx, handlers.UserContextKey{}, claims)

		next.ServeHTTP(w, r.WithContext(ctx))
	})
}
