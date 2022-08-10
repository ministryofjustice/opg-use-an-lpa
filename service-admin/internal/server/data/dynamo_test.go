package data_test

import (
	"context"
	"testing"

	"github.com/aws/aws-sdk-go-v2/config"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
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
			config, err := config.LoadDefaultConfig(context.Background(), config.WithRegion(tt.args.region))

			if err != nil {
				t.Errorf("error setting up test config")
			}
			got := data.NewDynamoConnection(config, tt.args.endpoint, tt.args.tablePrefix)
			if got.Prefix != tt.args.tablePrefix+"-" {
				t.Errorf("expected prefix %v got %v", got.Prefix, tt.args.tablePrefix)
			}

		})
	}
}
