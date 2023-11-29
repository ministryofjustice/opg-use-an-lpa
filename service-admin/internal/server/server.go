package server

import (
	"errors"
	"fmt"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/time"
	"net/http"
	"net/url"
	"os"

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
	db  data.DynamoConnection
	r   *mux.Router
	tw  handlers.TemplateWriterService
	aks data.ActivationKeyService
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

func NewAdminApp(db data.DynamoConnection, r *mux.Router, tw handlers.TemplateWriterService, aks data.ActivationKeyService) *app {
	return &app{db, r, tw, aks}
}

func (a *app) InitialiseServer(keyURL string, cognitoLogoutURL *url.URL) http.Handler {
	a.r.Handle("/helloworld", handlers.HelloHandler())

	authMiddleware := NewAuthorisationMiddleware(&auth.Token{SigningKey: &auth.SigningKey{PublicKeyURL: keyURL}})
	searchServer := *handlers.NewSearchServer(data.NewAccountService(a.db), data.NewLPAService(a.db), handlers.NewTemplateWriterService(), a.aks)
	statsServer := *handlers.NewStatsServer(data.NewStatisticsService(a.db), handlers.NewTemplateWriterService(), &time.ServerTime{})
	systemMessageServer := *handlers.NewSystemMessageServer(handlers.NewTemplateWriterService())

	JSONLoggingMiddleware := NewJSONLoggingMiddleware(log.Logger)
	templateMiddleware := NewTemplateMiddleware(LoadTemplates(os.DirFS("web/templates")))
	errorHandlingMiddleware := NewErrorHandlingMiddleware(a.tw)

	a.r.Handle("/helloworld", handlers.HelloHandler())
	a.r.Handle("/logout", handlers.LogoutHandler(cognitoLogoutURL))
	a.r.Handle("/", authMiddleware(http.HandlerFunc(searchServer.SearchHandler)))
	a.r.Handle("/stats", authMiddleware(http.HandlerFunc(statsServer.StatsHandler)))
	a.r.Handle("/system-message", authMiddleware(http.HandlerFunc(systemMessageServer.SystemMessageHandler)))
	a.r.PathPrefix("/").Handler(handlers.StaticHandler(os.DirFS("web/static")))

	return JSONLoggingMiddleware(templateMiddleware(errorHandlingMiddleware(a.r)))
}

func NewAuthorisationMiddleware(token *auth.Token) func(http.Handler) http.Handler {
	return func(h http.Handler) http.Handler {
		return auth.WithAuthorisation(h, token)
	}
}

func NewErrorHandlingMiddleware(tw handlers.TemplateWriterService) func(http.Handler) http.Handler {
	return func(h http.Handler) http.Handler {
		return WithErrorHandling(h, tw)
	}
}

func NewTemplateMiddleware(templates *Templates) func(http.Handler) http.Handler {
	return func(h http.Handler) http.Handler {
		return WithTemplates(h, templates)
	}
}

func NewJSONLoggingMiddleware(logger zerolog.Logger) func(http.Handler) http.Handler {
	return func(h http.Handler) http.Handler {
		return WithJSONLogging(h, logger)
	}
}

func WithErrorHandling(next http.Handler, templateWriter handlers.TemplateWriterService) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		var eh ErrorHandler = func(w http.ResponseWriter, i int) {
			t := "error.page.gohtml"
			switch i {
			case http.StatusForbidden:
				t = "notauthorised.page.gohtml"
			case http.StatusNotFound:
				t = "notfound.page.gohtml"
			}

			ws := templateWriter
			if err := ws.RenderTemplate(w, r.Context(), t, nil); err != nil {
				log.Panic().Err(err).Msg("")
			}

			w.WriteHeader(i) //Write header can only be done once, it needs to be last to ensure it is correct
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
