package server_test

import (
	"bytes"
	"encoding/json"
	"net/http"
	"testing"

	. "github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/hlog"
	"github.com/stretchr/testify/assert"
)

func TestWithJSONLogging(t *testing.T) {
	t.Parallel()

	next := http.HandlerFunc(func(rw http.ResponseWriter, r *http.Request) {
		// check logger has been attached to request context
		assert.IsType(t, &zerolog.Logger{}, hlog.FromRequest(r))
		rw.WriteHeader(200)
	})

	w := &bytes.Buffer{}
	log := zerolog.New(w)

	assert.HTTPStatusCode(t, WithJSONLogging(next, log).ServeHTTP, "GET", "/", nil, 200)

	err := json.Unmarshal(w.Bytes(), new(map[string]interface{}))
	if err != nil {
		t.Error("failed to write JSON log")
	}
}
