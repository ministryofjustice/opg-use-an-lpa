package handlers

import (
	"net/http"

	validation "github.com/go-ozzo/ozzo-validation"
	"github.com/rs/zerolog/log"
)

type search struct {
	Query  string
	Errors map[string]error
}

func (s *search) Validate() error {
	e := validation.ValidateStruct(s,
		validation.Field(&s.Query, validation.Required.Error("Enter a search query")),
	)

	if errs, ok := e.(validation.Errors); ok {
		s.Errors = errs
	}

	return e
}

func SearchHandler() http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		s := &search{
			Query: "",
		}

		if r.Method == "POST" {
			err := r.ParseForm()
			if err != nil {
				log.Error().Err(err).Msg("failed to parse form input")
			}

			s.Query = r.PostFormValue("query")

			err = s.Validate()
			if err != nil {
				log.Error().Err(err).Msg("")
			}
		}

		if err := RenderTemplate(w, r.Context(), "search", s); err != nil {
			log.Panic().Err(err).Msg(err.Error())
		}
	}
}
