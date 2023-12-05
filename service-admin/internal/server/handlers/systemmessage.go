package handlers

import (
	"github.com/rs/zerolog/log"
	"net/http"
)

type SystemMessageService interface{}

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
		if err != nil {
			//	log.Error().Err(err).Msg("failed to parse form input")
		}
	}

	// TODO pass textarea values in to template

	if err := s.templateService.RenderTemplate(w, r.Context(), "systemmessage.page.gohtml", nil); err != nil {
		log.Panic().Err(err).Msg(err.Error())
	}
}
