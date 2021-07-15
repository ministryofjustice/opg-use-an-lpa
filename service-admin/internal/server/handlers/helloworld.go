package handlers

import (
	"net/http"

	"github.com/rs/zerolog/log"
)

func HelloHandler() http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		log.Ctx(r.Context()).Debug().Msg("viewed the hello world page")

		if err := RenderTemplate(w, r.Context(), "helloworld.page.gohtml", nil); err != nil {
			log.Panic().Err(err).Msg(err.Error())
		}
	}
}
