package handlers

import (
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/rs/zerolog/log"
	"net/http"
)

type SystemMessageServer struct {
	systemMessageService data.SystemMessageService
	templateService      TemplateWriterService
}

func NewSystemMessageServer(systemMessageService data.SystemMessageService, templateWriterService TemplateWriterService) *SystemMessageServer {
	return &SystemMessageServer{
		systemMessageService: systemMessageService,
		templateService:      templateWriterService,
	}
}

func (s *SystemMessageServer) SystemMessageHandler(w http.ResponseWriter, r *http.Request) {
	search := &Search{
		Path: r.URL.Path,
	}

	if r.Method == "POST" {
		err := r.ParseForm()
		if err != nil {
			//	log.Error().Err(err).Msg("failed to parse form input")
		}
	}

	// TODO pass textarea values in to template

	if err := s.templateService.RenderTemplate(w, r.Context(), "systemmessage.page.gohtml", search); err != nil {
		log.Panic().Err(err).Msg(err.Error())
	}
}
