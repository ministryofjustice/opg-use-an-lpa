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
	// if no prefix, then return unchanged
	if s.Prefix == "" {
		return name
	}

	// if or no / to insert prefix next to, then return unchanged
	if !strings.Contains(name[1:], "/") {
		return name
	}

	i := strings.Index(name[1:], "/")

	x := name[:i+1] + "/" + s.Prefix + "/" + strings.TrimLeft(name[i+1:], "/")

	return x
}
