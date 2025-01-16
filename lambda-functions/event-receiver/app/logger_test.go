package main

import (
	"testing"

	"github.com/stretchr/testify/mock"
	"github.com/ministryofjustice/opg-use-an-lpa/app/mocks"
)

func TestLogger_Info(t *testing.T) {
    mockLogger := new(mocks.Logger)

    mockLogger.On("Info", "Test info log").Return()

    mockLogger.Info("Test info log")

    mockLogger.AssertExpectations(t)
}

func TestLogger_Warn(t *testing.T) {
    mockLogger := new(mocks.Logger)

    mockLogger.On("Warn", "Test warn log", mock.Anything).Return()

    mockLogger.Warn("Test warn log", nil)

    mockLogger.AssertExpectations(t)
}

func TestLogger_Error(t *testing.T) {
    mockLogger := new(mocks.Logger)

    mockLogger.On("Error", "Test error log", mock.Anything).Return()

    mockLogger.Error("Test error log", nil)

    mockLogger.AssertExpectations(t)
}
