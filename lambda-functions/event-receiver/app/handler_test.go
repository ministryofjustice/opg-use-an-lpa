package main

import (
	"testing"
	"context"
	"encoding/json"

	"github.com/stretchr/testify/mock"
	"github.com/stretchr/testify/assert"
	"github.com/ministryofjustice/opg-use-an-lpa/app/mocks"
	"github.com/aws/aws-lambda-go/events"
)
