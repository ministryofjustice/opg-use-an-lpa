package auth

import (
	"context"
	"crypto/ecdsa"
	"fmt"
	"io/ioutil"
	"net/http"
	"sync"
	"time"

	"github.com/golang-jwt/jwt/v4"
	"github.com/pkg/errors"
	"github.com/sethvargo/go-retry"
)

type SigningKey struct {
	PublicKeyURL string
}

var publicKeyCache = &sync.Map{}

func (k *SigningKey) Fetch(ctx context.Context, keyID string) (*ecdsa.PublicKey, error) {
	if k.PublicKeyURL == "" {
		return nil, errors.New("key URL not supplied to SigningKey struct")
	}

	url := fmt.Sprintf("%s/%s", k.PublicKeyURL, keyID)

	if key, ok := publicKeyCache.Load(url); ok {
		return key.(*ecdsa.PublicKey), nil
	}

	var pemBytes *[]byte

	r := retry.WithMaxDuration(3*time.Second, retry.NewConstant(500*time.Millisecond))
	if err := retry.Do(ctx, r, func(ctx context.Context) error {
		pem, err := fetchPEM(ctx, url)
		if err != nil {
			return retry.RetryableError(err)
		}

		pemBytes = pem
		return nil
	}); err != nil {
		return nil, errors.Wrap(err, "failed to fetch public key")
	}

	publicKey, err := jwt.ParseECPublicKeyFromPEM(*pemBytes)

	publicKeyCache.Store(url, publicKey)

	return publicKey, err
}

func fetchPEM(ctx context.Context, url string) (*[]byte, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
	if err != nil {
		return nil, errors.Wrapf(err, "not able to create request for public key")
	}

	client := &http.Client{}

	res, err := client.Do(req)
	if err != nil {
		return nil, errors.Wrapf(err, "not able to fetch key from %s", url)
	}

	defer res.Body.Close()

	pem, err := ioutil.ReadAll(res.Body)
	if err != nil {
		return nil, errors.Wrapf(err, "not able to read key from response")
	}

	return &pem, nil
}
