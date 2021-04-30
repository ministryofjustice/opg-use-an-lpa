package server

import (
	"io"
	"log"
	"net/http"
)

const HelloRoute = "/"

type myThings struct {
	Name string
}

type Template interface {
	ExecuteTemplate(io.Writer, string, interface{}) error
}

func HelloHandler(tmpl Template) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		if r.Method == http.MethodPost {
			http.SetCookie(w, &http.Cookie{Name: "login", Value: r.FormValue("username")})
			http.Redirect(w, r, SecretRoute, http.StatusFound)
			return
		}

		name := greeterService("Josh")
		vars := myThings{Name: name}

		if err := tmpl.ExecuteTemplate(w, "page", vars); err != nil {
			log.Println(err)
		}
	}
}

func greeterService(name string) string {
	return "Hello " + name
}
