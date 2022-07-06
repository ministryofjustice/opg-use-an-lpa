package server

import (
	"errors"
	"fmt"
	"net/http"
	"net/url"
	"os"

	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/gorilla/mux"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/auth"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/data"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/rs/zerolog"
	"github.com/rs/zerolog/log"
)

type ErrorHandler func(http.ResponseWriter, int)

type errorInterceptResponseWriter struct {
	http.ResponseWriter
	h ErrorHandler
}

type app struct {
	db *dynamodb.Client
	r  *mux.Router
	tw handlers.TemplateWriterService
}

var ErrPanicRecovery = errors.New("error handler recovering from panic()")

func (w *errorInterceptResponseWriter) WriteHeader(status int) {
	if status >= http.StatusBadRequest {
		w.h(w.ResponseWriter, status)
		w.h = nil
	} else {
		w.ResponseWriter.WriteHeader(status)
	}
}

func (w *errorInterceptResponseWriter) Write(p []byte) (int, error) {
	if w.h == nil {
		return len(p), nil
	}

	return w.ResponseWriter.Write(p)
}

func NewAdminApp(db *dynamodb.Client, r *mux.Router, tw handlers.TemplateWriterService) *app {
	return &app{db, r, tw}
}

func (a *app) InitialiseServer(keyURL string, cognitoLogoutURL *url.URL) http.Handler {
	a.r.Handle("/helloworld", handlers.HelloHandler())
	a.r.Handle("/logout", handlers.LogoutHandler(cognitoLogoutURL))

	authHandler := NewAuthorisationHandler(&auth.Token{SigningKey: &auth.SigningKey{PublicKeyURL: keyURL}})
	searchServer := *handlers.NewSearchServer(data.NewAccountService(a.db), data.NewLPAService(a.db), handlers.NewTemplateWriterService())
	a.r.Handle("/", authHandler(http.HandlerFunc(searchServer.SearchHandler)))

	a.r.PathPrefix("/").Handler(handlers.StaticHandler(os.DirFS("web/static")))

	JSONHandler := NewJSONHandler(log.Logger)
	templateHandler := NewTemplateHandler(LoadTemplates(os.DirFS("web/templates")))
	errorHandler := NewErrorHandler(a.tw)

	return JSONHandler(templateHandler(errorHandler(a.r)))
}

func NewAuthorisationHandler(token *auth.Token) func(http.Handler) http.Handler {
	return func(h http.Handler) http.Handler {
		return auth.WithAuthorisation(h, token)
	}
}

func NewErrorHandler(tw handlers.TemplateWriterService) func(http.Handler) http.Handler {
	return func(h http.Handler) http.Handler {
		return withErrorHandling(h, tw)
	}
}

func NewTemplateHandler(templates *Templates) func(http.Handler) http.Handler {
	return func(h http.Handler) http.Handler {
		return WithTemplates(h, templates)
	}
}

func NewJSONHandler(logger zerolog.Logger) func(http.Handler) http.Handler {
	return func(h http.Handler) http.Handler {
		return WithJSONLogging(h, logger)
	}
}

func withErrorHandling(next http.Handler, templateWriter handlers.TemplateWriterService) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		var eh ErrorHandler = func(w http.ResponseWriter, i int) {
			w.WriteHeader(i)

			t := "error.page.gohtml"
			switch i {
			case 403:
				t = "notauthorised.page.gohtml"
			case 404:
				t = "notfound.page.gohtml"
			}

			ws := templateWriter
			if err := ws.RenderTemplate(w, r.Context(), t, nil); err != nil {
				log.Panic().Err(err).Msg("")
			}
		}

		// panic recovery
		defer func() {
			if r := recover(); r != nil {
				var err error
				switch t := r.(type) {
				case string:
					err = fmt.Errorf("%w, %s", ErrPanicRecovery, t)
				}
				log.Error().Err(err).Stack().Msg("error handler recovering from panic()")

				eh(w, http.StatusInternalServerError)
			}
		}()

		next.ServeHTTP(&errorInterceptResponseWriter{w, eh}, r)
	})
}
