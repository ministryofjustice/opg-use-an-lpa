package handlers

import (
	"context"
	"errors"
	"net/http"
	"regexp"
	"strings"

	validation "github.com/go-ozzo/ozzo-validation"
	"github.com/go-ozzo/ozzo-validation/is"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/rs/zerolog/log"
)

type AccountService interface {
	GetActorUserByEmail(context.Context, string) (*data.ActorUser, error)
	GetEmailByUserID(context.Context, string) (string, error)
}

type LPAService interface {
	GetLpasByUserID(context.Context, string) ([]*data.LPA, error)
	GetLPAByActivationCode(context.Context, string) (*data.LPA, error)
}

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
	Query  string
	Type   queryType
	Result interface{}
	Errors validation.Errors
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

func stripUnnecessaryCharacters(code string) string {
	code = strings.ToUpper(code)
	result := strings.ReplaceAll(code, "C-", "")
	result = strings.ReplaceAll(result, "-", "")

	return result
}

func SearchHandler(accountService AccountService, lpaService LPAService) http.HandlerFunc {
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
			} else {
				s.Result = doSearch(r.Context(), accountService, lpaService, s.Type, s.Query)
			}
		}

		if err := RenderTemplate(w, r.Context(), "search.page.gohtml", s); err != nil {
			log.Panic().Err(err).Msg(err.Error())
		}
	}
}

func doSearch(ctx context.Context, accountService AccountService, lpaService LPAService, t queryType, q string) interface{} {
	switch t {
	case EmailQuery:
		r, err := accountService.GetActorUserByEmail(ctx, q)
		if err != nil {
			return nil
		}

		r.LPAs, err = lpaService.GetLpasByUserID(ctx, r.ID)
		if err != nil && !errors.Is(err, data.ErrUserLpaActorMapNotFound) {
			return nil
		}

		return r

	case ActivationCodeQuery:
		r, err := lpaService.GetLPAByActivationCode(ctx, stripUnnecessaryCharacters(q))
		if err != nil {
			return nil
		}

		email, err := accountService.GetEmailByUserID(ctx, r.UserID)

		if email == "" {
			email = "Not Found"
		}

		if err == nil {
			return map[string]interface{}{
				"Activation key": q,
				"Used":           "Yes",
				"Email":          email,
				"LPA":            r.SiriusUID,
			}
		}
	}

	return nil
}
