package main

import (
    "github.com/aws/aws-sdk-go-v2/aws"
)

type Factory interface {
    GetAWSConfig() aws.GetAWSConfig
    GetLogger() Logger
    GetConfig() AppConfig
}

type factory struct {
    awsConfig aws.GetAWSConfig
    logger Logger
    config AppConfig
}

func NewFactory(cfg aws.Config, logger Logger, config AppConfig) Factory {
    return &factory{
        awsConfig: cfg,
        logger: logger,
        config: config,
    }
}

func (f *factory) GetAWSConfig() aws.Config {
    return f.awsConfig
}

func (f *factory) GetLogger() Logger {
    return f.logger
}

func (f *factory) GetConfig() AppConfig {
    return f.config
}