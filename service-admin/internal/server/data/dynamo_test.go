package data_test

import (
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/stretchr/testify/assert"
	"reflect"
	"testing"
)

func TestNewDynamoConnection(t *testing.T) {
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
		t.Run(tt.name, func(t *testing.T) {
			got := data.NewDynamoConnection(tt.args.region, tt.args.endpoint, tt.args.tablePrefix)
			if got == nil {
				t.Errorf("Error nil dynamodb recieved")
			}
			v := reflect.ValueOf(*got)
			gotOptions := v.FieldByName("options")
			if gotOptions.FieldByName("Region").String() != tt.args.region {
				t.Errorf("expected options %v got %v", gotOptions.FieldByName("Region").String(), tt.args.region)
			}

		})
	}
}

func TestPrefixedTableName(t *testing.T) {
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
		t.Run(tt.name, func(t *testing.T) {
			data.NewDynamoConnection("", "", tt.prefix)
			assert.Equalf(t, tt.want, data.PrefixedTableName(tt.args.name), "PrefixedTableName(%v)", tt.args.name)
		})
	}
}
