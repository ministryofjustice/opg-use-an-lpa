package main

import (
	"crypto/ecdsa"
	"flag"
	"fmt"
	"io/ioutil"
	"net/http"
	"net/http/httputil"
	"net/url"
	"sync"
	"time"

	"github.com/golang-jwt/jwt"
	"github.com/ministryofjustice/opg-go-common/env"
)

var privateKeyCache = &sync.Map{}

type Claims struct {
	Sub   string `json:"sub"`
	Name  string `json:"name"`
	Email string `json:"email"`
}

func (c Claims) Valid() error {
	return nil
}

func loadPrivateKey(pemPath string) (*ecdsa.PrivateKey, error) {
	if key, ok := privateKeyCache.Load(pemPath); ok {
		return key.(*ecdsa.PrivateKey), nil
	}

	pem, err := ioutil.ReadFile(pemPath)
	if err != nil {
		return nil, fmt.Errorf("unable to load private key pem file from %s, %w", pemPath, err)
	}

	privateKey, err := jwt.ParseECPrivateKeyFromPEM(pem)
	if err != nil {
		return nil, fmt.Errorf("unable to parse ec private key from data in %s, %w", pemPath, err)
	}

	privateKeyCache.Store(pemPath, privateKey)

	return privateKey, nil
}

func createToken(keyPath string, name string, email string) (string, error) {
	t := jwt.New(jwt.GetSigningMethod("ES256"))

	t.Claims = &Claims{
		Sub:   "123456789",
		Name:  name,
		Email: email,
	}

	t.Header["kid"] = "public-key"
	t.Header["signer"] = "local"
	t.Header["expiration"] = time.Now().Add(time.Minute * 1).Unix()

	key, err := loadPrivateKey(keyPath)
	if err != nil {
		return "", fmt.Errorf("unable to load key, %w", err)
	}

	return t.SignedString(key)
}

// Given a request send it to the appropriate url
func proxyRequest(proxyHost string, privKeyPath string, name string, email string) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		// parse the url
		url, _ := url.Parse(proxyHost)

		// create the reverse proxy
		proxy := httputil.NewSingleHostReverseProxy(url)

		// Update the headers to allow for SSL redirection
		r.URL.Host = url.Host
		r.URL.Scheme = url.Scheme
		r.Host = url.Host

		t, err := createToken(privKeyPath, name, email)
		if err != nil {
			panic(err)
		}

		r.Header.Add("X-Amzn-Oidc-Data", t)

		// Note that ServeHttp is non blocking and uses a go routine under the hood
		proxy.ServeHTTP(w, r)
	}
}

func servePublicKey(publicKeyPath string) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		keyFile, err := ioutil.ReadFile(publicKeyPath)
		if err != nil {
			panic(err)
		}

		w.Header().Add("Content-Type", "application/x-pem-file")
		fmt.Fprint(w, string(keyFile))
	}
}

func main() {
	var (
		port           = flag.String("port", env.Get("PROXY_PORT", "5000"), "The port to run the proxy on")
		proxyHost      = flag.String("host", env.Get("PROXY_HOST", "http://127.0.0.1:9005"), "The url of the application to proxy")
		privateKeyPath = flag.String("privkey", env.Get("PROXY_PRIVATE_KEY", "key.pem"), "The path to an ECDSA private key file")
		publicKeyPath  = flag.String("pubkey", env.Get("PROXY_PUBLIC_KEY", "pub-key.pem"), "The path to the corresponding ECDSA public key file")
		username       = flag.String("name", env.Get("PROXY_CLAIM_NAME", "Use Test User"), "A name to attach to the JWT claims")
		email          = flag.String(
			"email",
			env.Get("PROXY_CLAIM_EMAIL", "opg-use-an-lpa+test-user@digital.justice.gov.uk"),
			"An email address to attach to the JWT claims",
		)
	)

	flag.Parse()

	// start server
	http.HandleFunc("/public-key", servePublicKey(*publicKeyPath))
	http.HandleFunc("/", proxyRequest(*proxyHost, *privateKeyPath, *username, *email))

	fmt.Printf("Proxying for %s on %s", *proxyHost, fmt.Sprintf(":%s", *port))

	if err := http.ListenAndServe(fmt.Sprintf(":%s", *port), nil); err != nil {
		panic(err)
	}
}
