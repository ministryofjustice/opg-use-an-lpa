package handlers

import (
	"net/http"
	"net/url"
	"fmt"

	"github.com/rs/zerolog/log"
)

func LogoutHandler(cognitoLogoutURL *url.URL) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {                
		log.Ctx(r.Context()).Debug().Msg("Admin logged out")

		l := &url.URL{
			Scheme: "https",
			Host: r.Host,
			Path: r.RequestURI,
		}
		
		v := cognitoLogoutURL.Query()
		v.Set("logout_uri", l.String())
		cognitoLogoutURL.RawQuery = v.Encode()

		fmt.Println(cognitoLogoutURL)
		
		http.Redirect(w, r, cognitoLogoutURL.String(), 301)

		if err := RenderTemplate(w, r.Context(), "logout.page.gohtml", nil); err != nil {
			log.Panic().Err(err).Msg(err.Error())
		}
	}
}
