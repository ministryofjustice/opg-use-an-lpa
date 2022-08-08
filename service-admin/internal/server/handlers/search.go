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

type TemplateWriterService interface {
	RenderTemplate(http.ResponseWriter, context.Context, string, interface{}) error
}

type ActivationKeyService interface {
	GetActivationKeyFromCodesEndpoint(context.Context, string) (*data.ActivationKeys, error)
}

type SearchServer struct {
	accountService       AccountService
	lpaService           LPAService
	templateService      TemplateWriterService
	activationKeyService ActivationKeyService
}

const (
	EmailQuery QueryType = iota
	ActivationCodeQuery
)

var (
	//nolint:stylecheck // actual message shown to users
	ErrNotEmailOrCode error = errors.New("Enter an email address or activation code")

	activationCodeRegexp *regexp.Regexp = regexp.MustCompile(`(?i)^c(-|)[a-z0-9]{4}(-|)[a-z0-9]{4}(-|)[a-z0-9]{4}$`)
)

type QueryType int

type Search struct {
	Query  string
	Type   QueryType
	Result interface{}
	Errors validation.Errors
}

func NewSearchServer(accountService AccountService, lpaService LPAService, templateWriterService TemplateWriterService, activationKeyService ActivationKeyService) *SearchServer {
	return &SearchServer{
		accountService:       accountService,
		lpaService:           lpaService,
		templateService:      templateWriterService,
		activationKeyService: activationKeyService,
	}
}

func (s *Search) Validate() error {
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

func (s *Search) checkEmailOrCode(value interface{}) error {
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

func (searchServer *SearchServer) SearchHandler(w http.ResponseWriter, r *http.Request) {
	s := &Search{}

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
			s.Result = searchServer.DoSearch(r.Context(), s.Type, s.Query)
		}
	}

	if err := searchServer.templateService.RenderTemplate(w, r.Context(), "search.page.gohtml", s); err != nil {
		log.Panic().Err(err).Msg(err.Error())
	}
}

func (s *SearchServer) DoSearch(ctx context.Context, t QueryType, q string) interface{} {
	switch t {
	case EmailQuery:
		r, err := s.accountService.GetActorUserByEmail(ctx, q)
		if err != nil {
			return nil
		}

		r.LPAs, err = s.lpaService.GetLpasByUserID(ctx, r.ID)
		if err != nil && !errors.Is(err, data.ErrUserLpaActorMapNotFound) {
			return nil
		}

		return r

	case ActivationCodeQuery:
		r, err := s.lpaService.GetLPAByActivationCode(ctx, stripUnnecessaryCharacters(q))

		if err == nil {
			email, err := s.accountService.GetEmailByUserID(ctx, r.UserID)
			if err != nil {
				return nil
			}
			if email == "" {
				email = "Not Found"
			}

			activationKey, err := s.activationKeyService.GetActivationKeyFromCodesEndpoint(ctx, stripUnnecessaryCharacters(q))

			if err != nil {
				return map[string]interface{}{
					"Activation key": q,
					"Used":           "Yes",
					"Email":          email,
					"LPA":            r.SiriusUID,
				}
			} else {

				for _, value := range *activationKey {
					return map[string]interface{}{
						"Activation key": q,
						"Used":           "Yes",
						"Email":          email,
						"LPA":            r.SiriusUID,
						"Status":         value.StatusDetails,
						"GeneratedDate":  value.GeneratedDate,
						"LastUpdated":    value.LastUpdatedDate,
					}
				}
			}
		} else {
			activationKey, err := s.activationKeyService.GetActivationKeyFromCodesEndpoint(ctx, stripUnnecessaryCharacters(q))
			if err == nil {
				for _, value := range *activationKey {
					return map[string]interface{}{
						"Activation key": q,
						"Used":           "No",
						"LPA":            value.Lpa,
						"Status":         value.StatusDetails,
						"GeneratedDate":  value.GeneratedDate,
						"LastUpdated":    value.LastUpdatedDate,
					}
				}
			}

		}

	}

	return nil
}
