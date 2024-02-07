package data

import (
	"context"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
	"strings"
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

func NewSSMConnection(client SSMClient, prefix string) *SSMConnection {
	return &SSMConnection{Client: client, Prefix: prefix}
}

func (s *SSMConnection) prefixedParameterName(name string) string {
	// try to insert after first / . If there's no / then just put it on the front
	i := strings.Index(name, "/")
	if i == -1 {
		i = 0
	}

	return name[:i] + s.Prefix + name[i+1:]
}
