package main

import (
	"log/slog"
    "net/http"
	"github.com/aws/aws-sdk-go-v2/aws"
)

type Factory struct {
	logger                *slog.Logger
	cfg                   aws.Config
	httpClient            *http.Client
}