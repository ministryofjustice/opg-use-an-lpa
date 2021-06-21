package server

import (
	"net/http"
	"os"

	ghndl "github.com/gorilla/handlers"
	"github.com/gorilla/mux"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
	"github.com/rs/zerolog/log"
)

func NewServer() http.Handler {
	router := mux.NewRouter()

	router.Handle("/", handlers.HelloHandler())
	router.PathPrefix("/").Handler(http.FileServer(http.Dir("web/static")))

	wrap := ghndl.RecoveryHandler()(
		WithJSONLogging(
			WithTemplates(
				router,
				LoadTemplates(os.DirFS("web/templates")),
			),
			log.Logger,
		),
	)

	return wrap
}
