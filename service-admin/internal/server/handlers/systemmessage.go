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
	if r.Method == "POST" {

		err := r.ParseForm()
		if err == nil {
			messages := map[string]string{
				"system-message-use-en":  r.PostFormValue("use-eng"),
				"system-message-use-cy":  r.PostFormValue("use-cy"),
				"system-message-view-en": r.PostFormValue("view-eng"),
				"system-message-view-cy": r.PostFormValue("view-cy"),
			}

			s.systemMessageService.PutSystemMessages(r.Context(), messages)
		} else {
			log.Error().Err(err).Msg("failed to parse form input")
		}
	}

	messages, _ := s.systemMessageService.GetSystemMessages(r.Context())
	if err := s.templateService.RenderTemplate(w, r.Context(), "systemmessage.page.gohtml", messages); err != nil {
		log.Panic().Err(err).Msg(err.Error())
	}
}
