package handlers

import (
	"net/http"
	"net/url"

	"github.com/rs/zerolog/log"
)

func LogoutHandler(cognitoLogoutURL *url.URL) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {   
		log.Ctx(r.Context()).Debug().Msg("Redirecting to cognito logout URL")

		cookie := &http.Cookie{
			Name: "AWSELBAuthSessionCookie-0",
			MaxAge: -1,
		}
		
		http.SetCookie(w,cookie)

		w.Header().Add("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0") // HTTP 1.1.
		w.Header().Add("Pragma", "no-cache"); // HTTP 1.0.
		w.Header().Add("Expires", "0"); // Proxies.

		l := &url.URL{
			Scheme: "https",
			Host: r.Host,
			Path: "/",
		}
		
		v := cognitoLogoutURL.Query()
		v.Set("logout_uri", l.String())
		cognitoLogoutURL.RawQuery = v.Encode()
	
		http.Redirect(w, r, cognitoLogoutURL.String(), 301)
	}
}
