package handlers

import (
	"errors"
	"net/http"
	"regexp"
	"strings"

	validation "github.com/go-ozzo/ozzo-validation"
	"github.com/go-ozzo/ozzo-validation/is"
	"github.com/rs/zerolog/log"
)

type search struct {
	Query   string
	Results map[string]interface{}
	Errors  validation.Errors
}

var (
	ErrIsNotEmailOrCode  error          = errors.New("Enter an email address or activation code") //nolint:stylecheck // actual message shown to users
	activationCodeRegexp *regexp.Regexp = regexp.MustCompile(`(?i)^C?(-|)[a-z0-9]{4}(-|)[a-z0-9]{4}(-|)[a-z0-9]{4}$`)
)

func (s *search) Validate() error {
	e := validation.ValidateStruct(s,
		validation.Field(&s.Query,
			validation.Required.Error("Enter a search query"),
			validation.By(checkEmailOrCode)),
	)

	if errs, ok := e.(validation.Errors); ok {
		s.Errors = errs
	}

	return e
}

func checkEmailOrCode(value interface{}) error {
	isEmail := is.Email.Validate(value)

	isCode := validation.Match(activationCodeRegexp).Validate(value)

	if isEmail != nil && isCode != nil {
		return ErrIsNotEmailOrCode
	}

	return nil
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

			s.Query = strings.ReplaceAll(r.PostFormValue("query"), " ", "")

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
