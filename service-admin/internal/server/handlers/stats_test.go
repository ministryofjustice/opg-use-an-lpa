package handlers_test

import (
	"testing"
	"net/http"
	"context"
	"net/http/httptest"
	"strings"
	"errors"
	"reflect"

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

func Test_GetAllMetrics(t *testing.T) {
	t.Parallel()

	type args struct {
		ctx       context.Context
		q         []string
	}

	tests := []struct {
		name                 string
		args                 args
		templateService      TemplateWriterService
		statisticsService    StatisticsService
		want                 interface{}
	}{
		{
			name: "Test retrieving all metrics query",
			args: args{
				ctx: context.TODO(),
				q: []string {"2022-11", "2022-10", "2022-09", "Total"},
			},
			statisticsService: &mockStatisticsService{
				GetAllMetricsFunc: func(ctx context.Context, list []string) (map[string]map[string]float64, error) {
					if list[0]  == "2022-11" {
						return map[string]map[string]float64{
							"2022-11": {
								"lpas_added": 1,
								"lpa_removed_event": 1,
								"account_created_event": 5,
							},
						}, nil
					}
					t.Errorf("expected a list value got %v", list[0])
					t.FailNow()
					return nil, nil	
				},
			},
			want: map[string]map[string]float64{
					"2022-11": {
						"lpas_added": 1,
						"lpa_removed_event": 1,
						"account_created_event": 5,
					},
				},
		},
		{
			name: "Test metrics query with error on time period lookup",
			args: args{
				ctx:       context.TODO(),
				q:         []string {"2022-11"},
			},
			statisticsService: &mockStatisticsService{
				GetAllMetricsFunc: func(ctx context.Context, list []string) (map[string]map[string]float64, error) {
					return nil, errors.New("this is an error")
				},
			},
			want:                 nil,
		},
	}
	

	for _, tt := range tests {
		tt := tt
		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			s := NewStatsServer(tt.statisticsService,tt.templateService)

			if got := s.GetMetricsInTheLastThreeMonths(tt.args.ctx); !reflect.DeepEqual(got, tt.want) {
				t.Errorf("GetMetricsInTheLastThreeMonths() = %v, want %v", got, tt.want)
			}
		})
	}
}

func TestTemplateErrorPanic(t *testing.T) {
	t.Parallel()

	testMetrics := &map[string]map[string]float64{
		"2022-11": {
			"lpas_added": 1,
			"lpa_removed_event": 1,
			"account_created_event": 5,
		},
		"2022-10": {
			"lpas_added": 4,
			"lpa_removed_event": 2,
			"account_created_event": 7,
		},
		"2022-09": {
			"lpas_added": 3,
			"lpa_removed_event": 1,
			"account_created_event": 4,
		},
		"Total": {
			"lpas_added": 10,
			"lpa_removed_event": 11,
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

		server := NewStatsServer(&mockStatisticsService{
			GetAllMetricsFunc: func(ctx context.Context, list []string) (map[string]map[string]float64, error) { return *testMetrics, nil },
		},
		ts,
		)
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