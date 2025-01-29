package main

import (
	"github.com/aws/aws-sdk-go-v2/aws"
	"log/slog"
	"net/http"
)

type Factory struct {
	logger     *slog.Logger
	cfg        aws.Config
	httpClient *http.Client
}
