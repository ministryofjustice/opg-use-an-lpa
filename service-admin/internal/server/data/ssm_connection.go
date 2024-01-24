package data

import (
	"context"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
)

type SSMClient interface {
	PutParameter(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error)
	GetParameter(ctx context.Context, params *ssm.GetParameterInput, optFns ...func(*ssm.Options)) (*ssm.GetParameterOutput, error)
	DeleteParameter(ctx context.Context, params *ssm.DeleteParameterInput, optFns ...func(*ssm.Options)) (*ssm.DeleteParameterOutput, error)
}

type SSMConnection struct {
	Prefix string
	Client SSMClient
}

func NewSSMConnection(client SSMClient) *SSMConnection {
	return &SSMConnection{Client: client}
}

func (s *SSMConnection) prefixedParameterName(name string) string {
	return s.Prefix + name
}
