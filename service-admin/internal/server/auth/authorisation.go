package auth

import (
	"context"
	"fmt"
	"net/http"

	"github.com/golang-jwt/jwt"
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

func ValidateJWT(ctx context.Context, token string, key *SigningKey) (*Claims, error) {
	claims := &Claims{}

	_, err := jwt.ParseWithClaims(token, claims, func(t *jwt.Token) (interface{}, error) {
		kid := t.Header["kid"].(string)

		key.URL = fmt.Sprintf("%s/%s", key.URL, kid)

		return key.Fetch(ctx)
	})
	if err != nil {
		return nil, err
	}

	return claims, nil
}

func WithAuthorisation(next http.Handler, keyURL string) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		ctx := r.Context()

		sKey := &SigningKey{
			URL: keyURL,
		}

		claims, err := ValidateJWT(ctx, r.Header.Get("x-amzn-oidc-data"), sKey)
		if err != nil {
			log.Err(err).Msg("failed to validate jwt")
			w.WriteHeader(http.StatusForbidden)
			return
		}

		log.Info().Msgf("%s accessed the service", claims.Email)

		ctx = context.WithValue(ctx, handlers.UserContextKey{}, claims)

		next.ServeHTTP(w, r.WithContext(ctx))
	})
}
