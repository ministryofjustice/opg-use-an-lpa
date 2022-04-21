package handlers

import (
	"net/http"

	"github.com/rs/zerolog/log"
)

func LoggedoutHandler() http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {                
		log.Ctx(r.Context()).Debug().Msg("Admin logged out")

		if err := RenderTemplate(w, r.Context(), "logout.page.gohtml", nil); err != nil {
			log.Panic().Err(err).Msg(err.Error())
		}
	}
}
