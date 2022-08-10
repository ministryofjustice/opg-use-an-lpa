package main

import (
	"context"
	"errors"
	"flag"
	"net/http"
	"net/url"
	"os"
	"os/signal"
	"time"

	v4 "github.com/aws/aws-sdk-go-v2/aws/signer/v4"
	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/gorilla/mux"
	"github.com/ministryofjustice/opg-go-common/env"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
	"github.com/rs/zerolog/pkgerrors"
)

func main() {
	var (
		port = flag.String(
			"port",
			env.Get("ADMIN_PORT", "9005"),
			"Port at which to serve the admin application",
		)
		dbEndpoint = flag.String(
			"db-endpoint",
			env.Get("AWS_DYNAMODB_ENDPOINT", ""),
			"Endpoint URL for the service DynamoDB instance",
		)
		dbRegion = flag.String(
			"dbRegion",
			env.Get("AWS_REGION", "eu-west-1"),
			"",
		)
		dbTablePrefix = flag.String(
			"dbTablePrefix",
			env.Get("ADMIN_DYNAMODB_TABLE_PREFIX", ""),
			"",
		)
		keyURL = flag.String(
			"signing-key",
			env.Get("ADMIN_JWT_SIGNING_KEY_URL", ""),
			"The baseURL at which the public key used by the authentication JWT will be found",
		)
		cognitoLogoutURL = flag.String(
			"logout-url",
			env.Get("ADMIN_LOGOUT_URL", ""),
			"The redirect url to logout user",
		)
		cognitoClientID = flag.String(
			"client-id",
			env.Get("ADMIN_CLIENT_ID", ""),
			"The aws client id for user",
		)
		lpaCodesEndpoint = flag.String(
			"lpa-codes-endpoint",
			env.Get("LPA_CODES_API_ENDPOINT", ""),
			"The codes enpoint",
		)
	)

	flag.Parse()

	v := url.Values{}
	v.Set("client_id", *cognitoClientID)

	u, _ := url.Parse(*cognitoLogoutURL)

	u.RawQuery = v.Encode()

	config, err := config.LoadDefaultConfig(context.TODO(), config.WithRegion("eu-west-1"))

	if err != nil {
		log.Panic()
	}

	dynamoDB := data.NewDynamoConnection(config, *dbEndpoint, *dbTablePrefix)

	cred, err := config.Credentials.Retrieve(context.TODO())

	activationKeyService := data.NewActivationKeyService(v4.NewSigner(), cred, *lpaCodesEndpoint+"/v1/code")

	zerolog.ErrorStackMarshaler = pkgerrors.MarshalStack

	app := server.NewAdminApp(*dynamoDB, mux.NewRouter(), handlers.NewTemplateWriterService(), activationKeyService)

	srv := &http.Server{
		Handler:      app.InitialiseServer(*keyURL, u),
		Addr:         ":" + *port,
		WriteTimeout: 10 * time.Second,
		ReadTimeout:  10 * time.Second,
	}

	go func() {
		log.Info().Str("port", *port).Msgf("server starting on address %s", srv.Addr)

		if err := srv.ListenAndServe(); !errors.Is(err, http.ErrServerClosed) {
			log.Fatal().Stack().Err(err).Msg("server exited")
		}
	}()

	c := make(chan os.Signal, 1)
	signal.Notify(c, os.Interrupt)

	defer func() {
		signal.Stop(c)
	}()

	sig := <-c
	log.Info().Msgf("got %s signal. quitting.", sig)

	tc, cnl := context.WithTimeout(context.Background(), 30*time.Second)
	defer cnl()

	if err := srv.Shutdown(tc); err != nil {
		log.Error().Stack().Err(err).Msg("failed to shutdown server successfully")
	}
}
