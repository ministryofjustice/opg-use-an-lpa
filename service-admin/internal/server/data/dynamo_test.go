package data_test

import (
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/stretchr/testify/assert"
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

func TestPrefixedTableName(t *testing.T) {
	t.Parallel()

	type args struct {
		name string
	}

	tests := []struct {
		name   string
		args   args
		prefix string
		want   string
	}{
		{
			name:   "test prefix added",
			args:   args{name: "table-name"},
			prefix: "prefixed",
			want:   "prefixed-table-name",
		},
	}
	for _, tt := range tests {
		tt := tt
		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()
			dynamodbConnection := data.NewDynamoConnection("", "", tt.prefix)
			assert.Equalf(t, tt.want, dynamodbConnection.PrefixedTableName(tt.args.name), "PrefixedTableName(%v)", tt.args.name)
		})
	}
}
