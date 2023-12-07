package data

import (
	"context"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
)

type SystemMessageService struct {
	ssmConn SSMConnection
}

func NewSystemMessageService(ssmConn SSMConnection) *SystemMessageService {
	return &SystemMessageService{ssmConn: ssmConn}
}

func (s *SystemMessageService) GetSystemMessages(ctx context.Context) (systemMessages map[string]string, err error) {
	messageKeys := []string{"use-en", "use-cy", "view-en", "view-cy"}
	messages := make(map[string]string)
	for _, messageKey := range messageKeys {
		messageText, _ := s.ssmConn.Client.GetParameter(ctx, &ssm.GetParameterInput{
			Name:           aws.String(messageKey),
			WithDecryption: aws.Bool(true),
		})
		messages[messageKey] = *messageText.Parameter.Value
	}

	return messages, nil
}

/*func (s *SSMConnection) WriteParameter(name string, value string) error {
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
}*/
