package handlers

import (
	"context"
	"net/http"

	"github.com/rs/zerolog/log"
)

type TemplateService interface {
	RenderTemplate(http.ResponseWriter, context.Context, string, interface{}) error
}

type StatsServer struct {
	templateService TemplateService
}

func NewStatsServer(templateService TemplateService) *StatsServer {
	return &StatsServer{
		templateService: templateService,
	}
}

func (s *StatsServer) StatsHandler(w http.ResponseWriter, r *http.Request) {
	if err := s.templateService.RenderTemplate(w, r.Context(), "stats.page.gohtml", nil); err != nil {
		log.Panic().Err(err).Msg(err.Error())
	}
}