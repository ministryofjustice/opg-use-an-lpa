package handlers

import (
	"context"
	"github.com/rs/zerolog/log"
	"net/http"
)

type TemplateSystemMessageService interface {
	RenderTemplate(http.ResponseWriter, context.Context, string, interface{}) error
}

type SystemMessageServer struct {
	templateService TemplateSystemMessageService
}

func NewSystemMessageServer(templateWriterService TemplateSystemMessageService) *SystemMessageServer {
	return &SystemMessageServer{
		templateService: templateWriterService,
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

	if err := s.templateService.RenderTemplate(w, r.Context(), "systemmessage.page.gohtml", search); err != nil {
		log.Panic().Err(err).Msg(err.Error())
	}
}
