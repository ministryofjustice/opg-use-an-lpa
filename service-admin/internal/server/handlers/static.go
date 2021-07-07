package handlers

import (
	"io/fs"
	"net/http"
	"strings"
)

// StaticHandler wraps a http.FileServer with some extra handling that allows us to trap
// errors of various types (mainly 404 not found) and allow us to return nicer error pages.
func StaticHandler(dir fs.FS) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		v := fs.ValidPath(strings.TrimLeft(r.URL.Path, "/"))
		if !v {
			w.WriteHeader(http.StatusNotFound)
			return
		}

		// check whether a file exists at the given path
		_, err := fs.Stat(dir, strings.TrimLeft(r.URL.Path, "/"))
		if err != nil {
			w.WriteHeader(http.StatusNotFound)
			return
		}

		// otherwise, use http.FileServer to serve the static file that we now know
		// definitely exists
		http.FileServer(http.FS(dir)).ServeHTTP(w, r)
	}
}
