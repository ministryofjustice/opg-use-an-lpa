package main

import (
    "github.com/aws/aws-sdk-go-v2/aws"
)

type Factory interface {
    GetAWSConfig() aws.Config
    GetLogger() Logger
}

type factory struct {
    awsConfig aws.Config
    logger Logger
}

func NewFactory(cfg aws.Config, logger Logger) Factory {
    return &factory{
        awsConfig: cfg,
        logger: logger,
    }
}

func (f *factory) GetAWSConfig() aws.Config {
    return f.awsConfig
}

func (f *factory) GetLogger() Logger {
    return f.logger
}
