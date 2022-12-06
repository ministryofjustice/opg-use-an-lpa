package handlers

import (
	"github.com/stretchr/testify/assert"
	"net/url"
	"testing"
)

func TestLogoutHandler(t *testing.T) {
	t.Parallel()

	var (
		cognitoURL = &url.URL{
			Host: "cognito",
			Path: "/logout",
		}
		handler = LogoutHandler(cognitoURL)
	)

	assert.HTTPRedirect(t, handler.ServeHTTP, "GET", "/logout", nil)
}
