package data

import (
	"context"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
)

type SSMClient interface {
	PutParameter(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error)
	GetParameter(ctx context.Context, params *ssm.GetParameterInput, optFns ...func(*ssm.Options)) (*ssm.GetParameterOutput, error)
}

type SSMConnection struct {
	Client SSMClient
}

func NewSSMConnection(client SSMClient) *SSMConnection {
	return &SSMConnection{Client: client}
}
