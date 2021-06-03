package handlers

import (
	"net/http"
)

func StaticHandler(staticPath string) http.Handler {
	return http.FileServer(http.Dir(staticPath))
}
