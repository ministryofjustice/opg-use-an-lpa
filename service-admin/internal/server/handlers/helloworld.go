package handlers

import (
	"net/http"

	"github.com/rs/zerolog/log"
)

func HelloHandler() http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		log.Ctx(r.Context()).Info().Msg("viewed the hello world page")

		if err := GetTemplate(r.Context(), "helloworld.gohtml").ExecuteTemplate(w, "default", nil); err != nil {
			log.Err(err).Msg("error whilst rendering template")
		}
	}
}
