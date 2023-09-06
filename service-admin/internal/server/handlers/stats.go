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

type TimeProvider interface {
	Now() time.Time
}

type StatsServer struct {
	statisticsService StatisticsService
	templateService   TemplateService
	timeProvider      TimeProvider
}

type StatsPageData struct {
	Result interface{}
	Path   string
}

func NewStatsServer(
	statisticsService StatisticsService,
	templateService TemplateService,
	timeProvider TimeProvider,
) *StatsServer {
	return &StatsServer{
		statisticsService: statisticsService,
		templateService:   templateService,
		timeProvider:      timeProvider,
	}
}

func (s *StatsServer) StatsHandler(w http.ResponseWriter, r *http.Request) {
	pageData := &StatsPageData{
		Result: s.GetMetricsInTheLastThreeMonths(r.Context()),
		Path:   r.URL.Path,
	}

	if err := s.templateService.RenderTemplate(w, r.Context(), "stats.page.gohtml", pageData); err != nil {
		log.Panic().Err(err).Msg(err.Error())
	}
}

func (s *StatsServer) GetMetricsInTheLastThreeMonths(ctx context.Context) interface{} {
	checkList := getPeriodsToBeChecked(s.timeProvider)

	r, err := s.statisticsService.GetAllMetrics(ctx, checkList)
	if err != nil {
		return nil
	}

	return r
}

func getPeriodsToBeChecked(timeProvider TimeProvider) []string {
	currentTime := timeProvider.Now()
	last3Month := currentTime.AddDate(0, -3, 0)

	firstDay := last3Month.AddDate(0, 2, 0)
	lastDay := last3Month.AddDate(0, 1, 0)

	timeLayout := "2006-01"

	currentMonth := currentTime.Format(timeLayout)
	lastMonth := firstDay.Format(timeLayout)
	monthBeforeLast := lastDay.Format(timeLayout)
	threeMonthsAgo := last3Month.Format(timeLayout)

	checkList := []string{currentMonth, lastMonth, monthBeforeLast, threeMonthsAgo, "Total"}

	return checkList
}
