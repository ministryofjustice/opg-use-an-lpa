package handlers

import (
	"context"
	"github.com/rs/zerolog/log"
	"net/http"
)

type SystemMessageService interface {
	GetSystemMessages(ctx context.Context) (systemMessages map[string]string, err error)
	PutSystemMessages(ctx context.Context, messages map[string]string) (deleted bool, err error)
}

type SystemMessageServer struct {
	systemMessageService SystemMessageService
	templateService      TemplateWriterService
}

func NewSystemMessageServer(systemMessageService SystemMessageService, templateWriterService TemplateWriterService) *SystemMessageServer {
	return &SystemMessageServer{
		systemMessageService: systemMessageService,
		templateService:      templateWriterService,
	}
}

type SystemMessageData struct {
	Messages       map[string]string
	ErrorMessage   *string
	SuccessMessage *string
	Path           string
}

func (s *SystemMessageServer) SystemMessageHandler(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()
	var errorMessage string
	templateData := SystemMessageData{}

	if r.Method == "POST" {
		err := r.ParseForm()
		if err != nil {
			log.Error().Err(err).Msg("failed to parse form input")
			http.Error(w, "Error parsing form input", http.StatusBadRequest)

			return
		}

		messages := make(map[string]string)
		messages["system-message-use-en"] = r.PostFormValue("use-eng")
		messages["system-message-use-cy"] = r.PostFormValue("use-cy")
		messages["system-message-view-en"] = r.PostFormValue("view-eng")
		messages["system-message-view-cy"] = r.PostFormValue("view-cy")

		// Checks English and Welsh are present
		if (messages["system-message-use-en"] == "" && messages["system-message-use-cy"] != "") ||
			(messages["system-message-use-en"] != "" && messages["system-message-use-cy"] == "") ||
			(messages["system-message-view-en"] == "" && messages["system-message-view-cy"] != "") ||
			(messages["system-message-view-en"] != "" && messages["system-message-view-cy"] == "") {
			errorMessage = "Both English and Welsh versions are required for each message"
		}

		if errorMessage == "" {
			deleted, err := s.systemMessageService.PutSystemMessages(ctx, messages)
			if err != nil {
				log.Error().Err(err).Msg("failed to update system messages")
				errorMessage = "Error updating system messages"
			} else if deleted {
				successMessage := "System message has been removed"
				templateData.SuccessMessage = &successMessage
			} else {
				successMessage := "System message has been updated"
				templateData.SuccessMessage = &successMessage
			}
		}

		templateData.Messages = messages
	} else {
		messages, err := s.systemMessageService.GetSystemMessages(ctx)
		if err != nil {
			log.Error().Err(err).Msg(err.Error())
			errorMessage = "Error retrieving system messages"
		}

		templateData.Messages = messages
	}

	if errorMessage != "" {
		templateData.ErrorMessage = &errorMessage
	}

	if err := s.templateService.RenderTemplate(w, ctx, "systemmessage.page.gohtml", templateData); err != nil {
		log.Error().Err(err).Msg(err.Error())
		http.Error(w, "error rendering template", http.StatusInternalServerError)
	}
}
