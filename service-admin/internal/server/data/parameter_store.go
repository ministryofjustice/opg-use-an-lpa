package data

import (
	"context"
	"fmt"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
	"github.com/aws/aws-sdk-go-v2/service/ssm/types"
)

type SSMClient interface {
	PutParameter(ctx context.Context, params *ssm.PutParameterInput, optFns ...func(*ssm.Options)) (*ssm.PutParameterOutput, error)
	GetParameter(ctx context.Context, params *ssm.GetParameterInput, optFns ...func(*ssm.Options)) (*ssm.GetParameterOutput, error)
}

type SSMConnection struct {
	Client SSMClient
}

func NewSSMConnection(conf aws.Config) *SSMConnection {
	svc := ssm.NewFromConfig(conf)
	return &SSMConnection{Client: svc}
}

func (s *SSMConnection) WriteParameter(name string, value string) error {
	_, err := s.Client.PutParameter(context.TODO(), &ssm.PutParameterInput{
		Name:  aws.String(name),
		Value: aws.String(value),
		Type:  types.ParameterTypeString,
	})

	if err != nil {
		return fmt.Errorf("error writing parameter: %w", err)
	}

	return nil
}

func (s *SSMConnection) ReadParameter(name string) (string, error) {
	resp, err := s.Client.GetParameter(context.TODO(), &ssm.GetParameterInput{
		Name:           aws.String(name),
		WithDecryption: aws.Bool(true),
	})

	if err != nil {
		return "", fmt.Errorf("error reading parameter: %w", err)
	}

	return *resp.Parameter.Value, nil
}
