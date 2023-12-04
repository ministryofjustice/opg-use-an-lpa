package handlers_test

import (
	"testing"
)

type MockSSMConnection struct{}

// 1. rendering template loads data from parameter store
// 2. save button calls appropriate functions (4 combinations) to save to parameter store

// template error panic test - do we need this?

func Test_RenderTemplateLoadsFromParameterStore(t *testing.T) {
	// mock class that loads
	t.Parallel()
	t.Errorf("Oh no !!!")
}
