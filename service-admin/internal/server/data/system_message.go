package data

import (
	"context"
	"errors"
	"fmt"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/service/ssm"
	"github.com/aws/aws-sdk-go-v2/service/ssm/types"
	"github.com/rs/zerolog/log"
)

type SystemMessageService struct {
	ssmConn SSMConnection
}

func NewSystemMessageService(ssmConn SSMConnection) *SystemMessageService {
	return &SystemMessageService{ssmConn: ssmConn}
}

func (s *SystemMessageService) GetSystemMessages(ctx context.Context) (systemMessages map[string]string, err error) {
	messageKeys := []string{"system-message-use-en", "system-message-use-cy", "system-message-view-en", "system-message-view-cy"}
	messages := make(map[string]string)

	for _, messageKey := range messageKeys {
		messageText, _ := s.ssmConn.Client.GetParameter(ctx, &ssm.GetParameterInput{
			Name:           aws.String(s.ssmConn.prefixedParameterName(messageKey)),
			WithDecryption: aws.Bool(true),
		})
		if messageText != nil {
			messages[messageKey] = *messageText.Parameter.Value
		}
	}

	return messages, nil
}

func (s *SystemMessageService) PutSystemMessages(ctx context.Context, messages map[string]string) (err error) {
	for messageKey, messageValue := range messages {
		if messageValue != "" {
			_, err := s.ssmConn.Client.PutParameter(ctx, &ssm.PutParameterInput{
				Name:      aws.String(s.ssmConn.prefixedParameterName(messageKey)),
				Value:     aws.String(messageValue),
				Type:      types.ParameterTypeString,
				Overwrite: aws.Bool(true),
			})
			if err != nil {
				return fmt.Errorf("error writing parameter: %w", err)
			}
		} else {
			_, err := s.ssmConn.Client.DeleteParameter(ctx, &ssm.DeleteParameterInput{
				Name: aws.String(s.ssmConn.prefixedParameterName(messageKey)),
			})

			if err != nil {
				var pnf *types.ParameterNotFound
				if errors.As(err, &pnf) {
					// Parameter not found, we can ignore this error
					log.Debug().Msg(fmt.Sprintf("not deleting parameter '%s' as it does not exist", messageKey))
				} else {
					// Handle other types of errors
					return fmt.Errorf("error deleting parameter: %w", err)
				}
			}
		}
	}

	return nil
}
