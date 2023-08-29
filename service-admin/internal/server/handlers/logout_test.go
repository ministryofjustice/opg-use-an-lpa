package handlers

import (
	"github.com/stretchr/testify/assert"
	"net/http/httptest"
	"net/url"
	"testing"
)

func TestLogoutHandler(t *testing.T) {
	t.Parallel()

	// Setup
	cognitoURL := &url.URL{
		Host: "cognito",
		Path: "/logout",
	}
	handler := LogoutHandler(cognitoURL)

	req := httptest.NewRequest("GET", "/logout", nil)
	rr := httptest.NewRecorder()

	// Invoke the handler
	handler.ServeHTTP(rr, req)

	// Check for a redirect.
	assert.Equal(t, 301, rr.Code) // Ensure it's a "Moved Permanently" redirect.

	// Verify the redirect URL.
	location, err := rr.Result().Location()
	assert.NoError(t, err)
	assert.Contains(t, location.String(), "cognito")
	assert.Contains(t, location.String(), "/logout")
	query := location.Query()
	assert.Contains(t, query, "logout_uri")

	//Check the expired cookie.
	cookie := rr.Result().Cookies()[0]
	assert.Equal(t, "AWSELBAuthSessionCookie-0", cookie.Name)
	assert.True(t, cookie.MaxAge < 0) // Ensure the cookie is set to expire immediately.

	// Check the caching headers.
	assert.Equal(t, "no-store, no-cache, must-revalidate, max-age=0", rr.Header().Get("Cache-Control"))
	assert.Equal(t, "no-cache", rr.Header().Get("Pragma"))
	assert.Equal(t, "0", rr.Header().Get("Expires"))
}
