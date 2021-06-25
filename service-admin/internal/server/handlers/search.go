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

const (
	EmailQuery queryType = iota
	ActivationCodeQuery
)

var (
	//nolint:stylecheck // actual message shown to users
	ErrNotEmailOrCode error = errors.New("Enter an email address or activation code")

	activationCodeRegexp *regexp.Regexp = regexp.MustCompile(`(?i)^c(-|)[a-z0-9]{4}(-|)[a-z0-9]{4}(-|)[a-z0-9]{4}$`)
)

type queryType int

type search struct {
	Query   string
	Type    queryType
	Results map[string]interface{}
	Errors  validation.Errors
}

func (s *search) Validate() error {
	e := validation.ValidateStruct(s,
		validation.Field(&s.Query,
			validation.Required.Error("Enter a search query"),
			validation.By(s.checkEmailOrCode)),
	)

	if errs, ok := e.(validation.Errors); ok {
		s.Errors = errs
	}

	return e
}

func (s *search) checkEmailOrCode(value interface{}) error {
	isEmail := is.Email.Validate(value)
	if isEmail == nil {
		s.Type = EmailQuery
		return nil
	}

	isCode := validation.Match(activationCodeRegexp).Validate(value)
	if isCode == nil {
		s.Type = ActivationCodeQuery
		return nil
	}

	return ErrNotEmailOrCode
}

func SearchHandler() http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		s := &search{}

		if r.Method == "POST" {
			err := r.ParseForm()
			if err != nil {
				log.Error().Err(err).Msg("failed to parse form input")
			}

			s.Query = strings.ReplaceAll(r.PostFormValue("query"), " ", "")

			err = s.Validate()
			if err != nil {
				log.Debug().AnErr("form-error", err).Msg("")
			}

			s.Results = doSearch(s.Type, s.Query)
		}

		if err := RenderTemplate(w, r.Context(), "search", s); err != nil {
			log.Panic().Err(err).Msg(err.Error())
		}
	}
}

func doSearch(t queryType, q string) map[string]interface{} {
	switch t {
	case EmailQuery:
		return nil
	case ActivationCodeQuery:
		return map[string]interface{}{
			"Activation key": q,
			"Used":           "Yes",
			"DateTime":       "2021-03-01T12:37:12Z+0000",
			"Email":          "name@email.com",
		}
	}

	return nil
}
