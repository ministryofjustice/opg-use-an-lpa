package time

import (
	"github.com/stretchr/testify/assert"
	"testing"
	"time"
)

func TestServerTime_Now(t *testing.T) {
	t.Parallel()

	serverTime := &ServerTime{}

	sut := serverTime.Now()

	assert.IsType(t, time.Time{}, sut)
}
