package handlers

import (
	"net/http"
	"net/url"

	"github.com/rs/zerolog/log"
)

func LogoutHandler(cognitoLogoutURL *url.URL) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {   
		log.Ctx(r.Context()).Debug().Msg("Redirecting to cognito logout URL")

		l := &url.URL{
			Scheme: "https",
			Host: r.Host,
			Path: "/logged-out",
		}
		
		v := cognitoLogoutURL.Query()
		v.Set("logout_uri", l.String())
		cognitoLogoutURL.RawQuery = v.Encode()
	
		http.Redirect(w, r, cognitoLogoutURL.String(), 301)
	}
}
