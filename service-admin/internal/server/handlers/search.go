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
	GetLpaRecordBySiriusID(ctx context.Context, lpaID string) ([]*data.LPA, error)
}

type TemplateWriterService interface {
	RenderTemplate(http.ResponseWriter, context.Context, string, interface{}) error
}

type SearchServer struct {
	accountService       AccountService
	lpaService           LPAService
	templateService      TemplateWriterService
	activationKeyService data.ActivationKeyService
}

type SearchResult struct {
	Query         string
	Used          string
	Email         string
	LPA           string
	ActivationKey *data.ActivationKey
}

const (
	EmailQuery QueryType = iota
	ActivationCodeQuery
	LPANumberQuery
)

var (
	//nolint:stylecheck // actual message shown to users
	ErrNotEmailOrCode = errors.New("Enter an email address or activation code")

	activationCodeRegexp = regexp.MustCompile(`(?i)^c(-|)[a-z0-9]{4}(-|)[a-z0-9]{4}(-|)[a-z0-9]{4}$`)
	lpaNumberRegex       = regexp.MustCompile(`(?i)(\d[ -]*?){12}$`)
)

type QueryType int

type Search struct {
	Query  string
	Type   QueryType
	Result interface{}
	Errors validation.Errors
	Path   string
}

type AddedBy struct {
	DateAdded   string
	Email       string
	ActivateBy  int
	ActivatedOn string
}

func NewSearchServer(accountService AccountService, lpaService LPAService, templateWriterService TemplateWriterService, activationKeyService data.ActivationKeyService) *SearchServer {
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

	isLpaNumber := validation.Match(lpaNumberRegex).Validate(value)
	if isLpaNumber == nil {
		s.Type = LPANumberQuery
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

func (s *SearchServer) SearchHandler(w http.ResponseWriter, r *http.Request) {
	search := &Search{
		Path: r.URL.Path,
	}

	if r.Method == "POST" {
		err := r.ParseForm()
		if err != nil {
			log.Error().Err(err).Msg("failed to parse form input")
		}

		search.Query = strings.ReplaceAll(r.PostFormValue("query"), " ", "")

		err = search.Validate()
		if err != nil {
			log.Debug().AnErr("form-error", err).Msg("")
		} else {
			switch search.Type {
			case LPANumberQuery:
				search.Result = s.SearchByLPANumber(r.Context(), stripUnnecessaryCharacters(search.Query))
			case EmailQuery:
				search.Query = strings.ToLower(search.Query)
				search.Result = s.SearchByEmail(r.Context(), search.Query)
			case ActivationCodeQuery:
				search.Result = s.SearchByActivationCode(r.Context(), stripUnnecessaryCharacters(search.Query))
			}
		}
	}

	if err := s.templateService.RenderTemplate(w, r.Context(), "search.page.gohtml", search); err != nil {
		log.Panic().Err(err).Msg(err.Error())
	}
}

func (s *SearchServer) SearchByLPANumber(ctx context.Context, q string) interface{} {
	r, err := s.lpaService.GetLpaRecordBySiriusID(ctx, q)
	if err != nil {
		return nil
	}

	emails := []AddedBy{}

	for _, userID := range r {
		email, err := s.accountService.GetEmailByUserID(ctx, userID.UserID)
		if err == nil {
			addedBy := AddedBy{
				Email:       email,
				DateAdded:   userID.Added,
				ActivateBy:  userID.ActivateBy,
				ActivatedOn: userID.ActivatedOn,
			}

			emails = append(emails, addedBy)
		}
	}

	return map[string]interface{}{
		"LPANumber": q,
		"AddedBy":   emails,
	}
}

func (s *SearchServer) SearchByEmail(ctx context.Context, q string) interface{} {
	r, err := s.accountService.GetActorUserByEmail(ctx, q)
	if err != nil {
		return nil
	}

	r.LPAs, err = s.lpaService.GetLpasByUserID(ctx, r.ID)
	if err != nil && !errors.Is(err, data.ErrUserLpaActorMapNotFound) {
		return nil
	}

	return r
}

func (s *SearchServer) SearchByActivationCode(ctx context.Context, q string) interface{} {
	r, err := s.lpaService.GetLPAByActivationCode(ctx, q)

	if err == nil {
		email, err := s.accountService.GetEmailByUserID(ctx, r.UserID)
		if err != nil {
			return nil
		}

		if email == "" {
			email = "Not Found"
		}

		activationKey, err := s.activationKeyService.GetActivationKeyFromCodes(ctx, q)

		if err != nil {
			return &SearchResult{
				Query:         q,
				Used:          "Yes",
				Email:         email,
				LPA:           r.SiriusUID,
				ActivationKey: nil,
			}
		} else {

			for _, value := range *activationKey {
				return &SearchResult{
					Query:         q,
					Used:          "Yes",
					Email:         email,
					LPA:           r.SiriusUID,
					ActivationKey: &value,
				}
			}
		}
	} else {
		activationKey, err := s.activationKeyService.GetActivationKeyFromCodes(ctx, q)
		if err == nil {
			for _, value := range *activationKey {

				used := isUsed(value.Active, value.StatusDetails)

				return &SearchResult{
					Query:         q,
					Used:          used,
					ActivationKey: &value,
					LPA:           value.Lpa,
				}
			}
		}

	}

	return nil
}

func isUsed(active bool, status string) string {
	if !active && status == "Revoked" {
		return "Yes"
	} else {
		return "No"
	}
}
