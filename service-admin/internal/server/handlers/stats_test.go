package handlers_test

import (
	"context"
	"errors"
	"net/http"
	"net/http/httptest"
	"reflect"
	"strings"
	"testing"
	"time"

	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
)

type mockStatisticsService struct {
	GetAllMetricsFunc func(context.Context, []string) (map[string]map[string]float64, error)
}

func (m *mockStatisticsService) GetAllMetrics(ctx context.Context, list []string) (map[string]map[string]float64, error) {
	if m.GetAllMetricsFunc != nil {
		return m.GetAllMetricsFunc(ctx, list)
	}

	return map[string]map[string]float64{}, nil
}

type mockTimeProvider struct {
	NowFunk func() time.Time
}

func (m *mockTimeProvider) Now() time.Time {
	if m.NowFunk != nil {
		return m.NowFunk()
	}

	return time.Now()
}

func Test_GetAllMetrics(t *testing.T) {
	t.Parallel()

	tests := []struct {
		name              string
		templateService   TemplateWriterService
		statisticsService StatisticsService
		timeProvider      TimeProvider
		want              interface{}
	}{
		{
			name: "Test retrieving all metrics query",
			statisticsService: &mockStatisticsService{
				GetAllMetricsFunc: func(ctx context.Context, list []string) (map[string]map[string]float64, error) {
					if list[0] == "2022-11" {
						return map[string]map[string]float64{
							"2022-11": {
								"lpas_added":            1,
								"lpa_removed_event":     1,
								"account_created_event": 5,
							},
						}, nil
					}
					t.Errorf("expected a list value got %v", list[0])
					t.FailNow()
					return nil, nil
				},
			},
			timeProvider: &mockTimeProvider{
				NowFunk: func() time.Time {
					now, _ := time.Parse(time.RFC3339, "2022-11-01T15:04:05Z")
					return now
				},
			},
			want: map[string]map[string]float64{
				"2022-11": {
					"lpas_added":            1,
					"lpa_removed_event":     1,
					"account_created_event": 5,
				},
			},
		},
		{
			name: "Test metrics query with error on time period lookup",
			statisticsService: &mockStatisticsService{
				GetAllMetricsFunc: func(ctx context.Context, list []string) (map[string]map[string]float64, error) {
					return nil, errors.New("this is an error")
				},
			},
			timeProvider: &mockTimeProvider{},
			want:         nil,
		},
	}

	for _, tt := range tests {
		tt := tt
		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			s := NewStatsServer(tt.statisticsService, tt.templateService, tt.timeProvider)

			if got := s.GetMetricsInTheLastThreeMonths(context.TODO()); !reflect.DeepEqual(got, tt.want) {
				t.Errorf("GetMetricsInTheLastThreeMonths() = %v, want %v", got, tt.want)
			}
		})
	}
}

func TestTemplateErrorPanic(t *testing.T) {
	t.Parallel()

	testMetrics := &map[string]map[string]float64{
		"2022-11": {
			"lpas_added":            1,
			"lpa_removed_event":     1,
			"account_created_event": 5,
		},
		"2022-10": {
			"lpas_added":            4,
			"lpa_removed_event":     2,
			"account_created_event": 7,
		},
		"2022-09": {
			"lpas_added":            3,
			"lpa_removed_event":     1,
			"account_created_event": 4,
		},
		"Total": {
			"lpas_added":            10,
			"lpa_removed_event":     11,
			"account_created_event": 12,
		},
	}

	t.Run("Template error ends in panic", func(t *testing.T) {
		t.Parallel()

		ts := &mockTemplateWriterService{
			RenderTemplateFunc: func(w http.ResponseWriter, ctx context.Context, s string, i interface{}) error {
				return errors.New("I have errored")
			},
		}

		ss := &mockStatisticsService{
			GetAllMetricsFunc: func(ctx context.Context, list []string) (map[string]map[string]float64, error) {
				return *testMetrics, nil
			},
		}

		server := NewStatsServer(ss, ts, &mockTimeProvider{})

		reader := strings.NewReader("")
		var req *http.Request

		req, _ = http.NewRequest("GET", "/my_url", reader)

		req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
		w := httptest.NewRecorder()

		//recover panic
		defer func() { _ = recover() }()

		server.StatsHandler(w, req)

		t.Errorf("did not panic")

	})
}
