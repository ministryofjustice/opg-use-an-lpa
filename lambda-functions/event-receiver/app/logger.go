package main

import (
	"log"
)

type Logger interface {
    Info(message string, args ...interface{})
    Warn(message string, err error, args ...interface{})
    Error(message string, err error)
}

type defaultLogger struct{}

func NewLogger() Logger {
    return &defaultLogger{}
}

func (l *defaultLogger) Info(message string, args ...interface{}) {
    log.Printf("[INFO] "+message, args...)
}

func (l *defaultLogger) Warn(message string, err error, args ...interface{}) {
    log.Printf("[WARN] "+message+ ": %v", append(args, err)...)
}

func (l *defaultLogger) Error(message string, err error) {
    log.Printf("[ERROR] %s: %v"+message, err)
}
