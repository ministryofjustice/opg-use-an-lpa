package data_test

import (
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"testing"
)

func TestNewDynamoConnection(t *testing.T) {
	t.Parallel()

	type args struct {
		region      string
		endpoint    string
		tablePrefix string
	}

	tests := []struct {
		name string
		args args
	}{
		{
			name: "test dynamo db given",
			args: args{
				region:      "",
				endpoint:    "ENDPOINT",
				tablePrefix: "someprefix",
			},
		},
		{
			name: "test dynamo db given with region",
			args: args{
				region:      "some region",
				endpoint:    "ENDPOINT",
				tablePrefix: "someprefix",
			},
		},
	}
	for _, tt := range tests {
		tt := tt
		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()
			got := data.NewDynamoConnection(tt.args.region, tt.args.endpoint, tt.args.tablePrefix)
			if got.Prefix != tt.args.tablePrefix+"-" {
				t.Errorf("expected prefix %v got %v", got.Prefix, tt.args.tablePrefix)
			}

		})
	}
}
