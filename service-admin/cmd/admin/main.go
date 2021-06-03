package main

import (
	"context"
	"net/http"
	"os"
	"os/signal"
	"time"

	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server"
	"github.com/rs/zerolog/log"
)

func main() {
	port := getEnv("PORT", "9004")

	srv := &http.Server{
		Handler:      server.NewServer(),
		Addr:         ":" + port,
		WriteTimeout: 10 * time.Second,
		ReadTimeout:  10 * time.Second,
	}

	go func() {
		if err := srv.ListenAndServe(); err != http.ErrServerClosed {
			log.Fatal().AnErr("error", err).Msg("server exited")
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
		log.Error().AnErr("error", err).Msg("failed to shutdown server successfully")
	}
}

func getEnv(key, def string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}

	return def
}
