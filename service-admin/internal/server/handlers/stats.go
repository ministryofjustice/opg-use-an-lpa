package handlers

import (
	"context"
	"net/http"
	"time"

	"github.com/rs/zerolog/log"
)

type StatisticsService interface {
	GetAllMetrics(context.Context, []string) (map[string]map[string]float64, error)
}

type TemplateService interface {
	RenderTemplate(http.ResponseWriter, context.Context, string, interface{}) error
}

type StatsServer struct {
	statisticsService StatisticsService
	templateService   TemplateService
}

func NewStatsServer(statisticsService StatisticsService, templateService TemplateService) *StatsServer {
	return &StatsServer{
		statisticsService: statisticsService,
		templateService:   templateService,
	}
}

func (s *StatsServer) StatsHandler(w http.ResponseWriter, r *http.Request) {
	search := &Search{}

	search.Result = s.GetMetricsInTheLastThreeMonths(r.Context())

	if err := s.templateService.RenderTemplate(w, r.Context(), "stats.page.gohtml", search); err != nil {
		log.Panic().Err(err).Msg(err.Error())
	}
}

func (s *StatsServer) GetMetricsInTheLastThreeMonths(ctx context.Context) interface{} {
	checkList := getPeriodsToBeChecked()

	r, err := s.statisticsService.GetAllMetrics(ctx, checkList)
	if err != nil {
		return nil
	}

	return r
}

func getPeriodsToBeChecked() []string {
	currentTime := time.Now()
	last3Month := currentTime.AddDate(0, -3, 0)

	firstDay := last3Month.AddDate(0, 2, 0)
	lastDay := last3Month.AddDate(0, 1, 0)

	timeLayout := "2006-01"

	currentMonth := currentTime.Format(timeLayout)
	lastMonth := firstDay.Format(timeLayout)
	monthBeforeLast := lastDay.Format(timeLayout)
	checkList := []string{currentMonth, lastMonth, monthBeforeLast, "Total"}

	return checkList
}
