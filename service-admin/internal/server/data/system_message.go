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

func (s *SystemMessageService) GetSystemMessages(ctx context.Context) (map[string]string, error) {
	messageKeys := []string{"system-message-use-en", "system-message-use-cy", "system-message-view-en", "system-message-view-cy"}
	messages := make(map[string]string)

	for _, messageKey := range messageKeys {
		messageText, err := s.ssmConn.Client.GetParameter(ctx, &ssm.GetParameterInput{
			Name:           aws.String(s.ssmConn.prefixedParameterName(messageKey)),
			WithDecryption: aws.Bool(true),
		})
		if err != nil {
			log.Error().Err(err).Msg(fmt.Sprintf("error retrieving parameter: %s", messageKey))
			continue
		}

		if messageText != nil && messageText.Parameter != nil && messageText.Parameter.Value != nil {
			messages[messageKey] = *messageText.Parameter.Value
		}
	}

	return messages, nil
}

func (s *SystemMessageService) PutSystemMessages(ctx context.Context, messages map[string]string) (bool, error) {
	deleted := false

	for messageKey, messageValue := range messages {
		if messageValue != "" {
			_, err := s.ssmConn.Client.PutParameter(ctx, &ssm.PutParameterInput{
				Name:      aws.String(s.ssmConn.prefixedParameterName(messageKey)),
				Value:     aws.String(messageValue),
				Type:      types.ParameterTypeString,
				Overwrite: aws.Bool(true),
			})
			if err != nil {
				return false, fmt.Errorf("error writing parameter: %w", err)
			}
		} else {
			_, err := s.ssmConn.Client.DeleteParameter(ctx, &ssm.DeleteParameterInput{
				Name: aws.String(s.ssmConn.prefixedParameterName(messageKey)),
			})
			if err != nil {
				var pnf *types.ParameterNotFound
				if errors.As(err, &pnf) {
					log.Debug().Msg(fmt.Sprintf("not deleting parameter '%s' as it does not exist", messageKey))
				} else {
					return false, fmt.Errorf("error deleting parameter: %w", err)
				}
			} else {
				deleted = true
			}
		}
	}

	return deleted, nil
}
