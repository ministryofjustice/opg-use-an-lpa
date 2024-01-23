package handlers

import (
	"context"
	"github.com/rs/zerolog/log"
	"net/http"
)

type SystemMessageService interface {
	GetSystemMessages(ctx context.Context) (systemMessages map[string]string, err error)
	PutSystemMessages(ctx context.Context, messages map[string]string) (err error)
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

func (s *SystemMessageServer) SystemMessageHandler(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	if r.Method == "POST" {
		err := r.ParseForm()
		if err != nil {
			log.Error().Err(err).Msg("failed to parse form input")

			http.Error(w, "Error parsing form input", http.StatusBadRequest)
			return
		}

		messages := map[string]string{
			"system-message-use-en":  r.PostFormValue("use-eng"),
			"system-message-use-cy":  r.PostFormValue("use-cy"),
			"system-message-view-en": r.PostFormValue("view-eng"),
			"system-message-view-cy": r.PostFormValue("view-cy"),
		}

		// Checks English and Welsh are present
		if (messages["system-message-use-en"] == "" && messages["system-message-use-cy"] != "") ||
			(messages["system-message-use-en"] != "" && messages["system-message-use-cy"] == "") ||
			(messages["system-message-view-en"] == "" && messages["system-message-view-cy"] != "") ||
			(messages["system-message-view-en"] != "" && messages["system-message-view-cy"] == "") {
			http.Error(w, "Both English and Welsh versions are required for each message", http.StatusBadRequest)
			return
		}

		err = s.systemMessageService.PutSystemMessages(ctx, messages)
		if err != nil {
			log.Error().Err(err).Msg("failed to store system messages")
			http.Error(w, "Error storing system messages", http.StatusInternalServerError)
			return
		}
	}

	messages, err := s.systemMessageService.GetSystemMessages(ctx)
	if err != nil {
		log.Panic().Err(err).Msg(err.Error())
		http.Error(w, "Error retrieving system messages", http.StatusInternalServerError)
		return
	}

	if err := s.templateService.RenderTemplate(w, ctx, "systemmessage.page.gohtml", messages); err != nil {
		log.Panic().Err(err).Msg(err.Error())
		http.Error(w, "error rendering template", http.StatusInternalServerError)
	}
}
