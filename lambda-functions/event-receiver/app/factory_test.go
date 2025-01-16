package main

import (
	"testing"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/stretchr/testify/assert"
	"github.com/ministryofjustice/opg-use-an-lpa/app/mocks"
)

func TestFactory_GetLogger(t *testing.T) {
    mockLogger := new(mocks.Logger)
    mockAWSConfig := aws.Config{}

    factory := NewFactory(mockAWSConfig, mockLogger)

    assert.Equal(t, mockLogger, factory.GetLogger())
}

func TestFactory_GetAWSConfig(t *testing.T) {
    mockLogger := new(mocks.Logger)
    mockAWSConfig := aws.Config{}

    factory := NewFactory(mockAWSConfig, mockLogger)

    assert.Equal(t, mockAWSConfig, factory.GetAWSConfig())
}
