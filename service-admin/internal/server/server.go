package server

import (
	"net/http"

	ghndl "github.com/gorilla/handlers"
	"github.com/gorilla/mux"
	"github.com/ministryofjustice/opg-use-an-lpa/service-admin/internal/server/handlers"
)

func NewServer() http.Handler {
	router := mux.NewRouter()

	router.Handle("/", handlers.HelloHandler())
	router.PathPrefix("/").Handler(handlers.StaticHandler("web/static"))

	wrap := ghndl.RecoveryHandler()(
		JsonLogging(
			AttachTemplates(
				router,
				LoadTemplates("web/templates"),
			),
		),
	)

	return wrap
}
