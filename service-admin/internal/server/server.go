package server

import (
	"errors"
	"fmt"
	"net/http"
	"os"

	"github.com/aws/aws-sdk-go-v2/service/dynamodb"
	"github.com/gorilla/mux"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/auth"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/rs/zerolog/log"
)

type ErrorHandler func(http.ResponseWriter, int)

type errorInterceptResponseWriter struct {
	http.ResponseWriter
	h ErrorHandler
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

func NewServer(db *dynamodb.Client, keyURL string) http.Handler {
	router := mux.NewRouter()

	router.Handle("/helloworld", handlers.HelloHandler())
	router.Handle(
		"/",
		auth.WithAuthorisation(
			handlers.SearchHandler(db),
			&auth.Token{SigningKey: &auth.SigningKey{PublicKeyURL: keyURL}},
		),
	)
	router.PathPrefix("/").Handler(handlers.StaticHandler(os.DirFS("web/static")))

	wrap := WithJSONLogging(
		WithTemplates(
			withErrorHandling(router),
			LoadTemplates(os.DirFS("web/templates")),
		),
		log.Logger,
	)

	return wrap
}

func withErrorHandling(next http.Handler) http.Handler {
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

			if err := handlers.RenderTemplate(w, r.Context(), t, nil); err != nil {
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
