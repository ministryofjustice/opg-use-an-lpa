package server

import (
	"html/template"
	"log"
	"net/http"
)

const SecretRoute = "/secret"

func SecretHandler(tmpl *template.Template) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		canView := false

		permissions := r.Context().Value("permissions").([]string)
		for _, p := range permissions {
			if p == "view-secret" {
				canView = true
			}
		}

		if err := tmpl.ExecuteTemplate(w, "page", canView); err != nil {
			log.Println(err)
		}
	}
}
