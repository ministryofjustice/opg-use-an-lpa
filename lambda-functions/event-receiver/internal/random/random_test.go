package random

import (
	"github.com/ministryofjustice/opg-use-an-lpa/internal/random"
	"github.com/stretchr/testify/assert"
	"testing"
)

func TestRandomString(t *testing.T) {
	for _, length := range []int{1, 10, 100, 999} {
		got := random.String(length)

		assert.Len(t, got, length)
	}
}

func TestRandomCode(t *testing.T) {
	for _, length := range []int{1, 10, 100, 999} {
		got := random.Code(length)

		assert.Len(t, got, length)
	}
}
