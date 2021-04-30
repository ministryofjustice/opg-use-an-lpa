package main

import (
	"context"
	"html/template"
	"log"
	"net/http"
	"path/filepath"

	"github.com/ministryofjustice/opg-use-an-lpa/admin-thing/internal/server"
)

type User struct {
	Username    string
	Permissions []string
}

func main() {
	webDir := "web"

	userDB := []User{
		{
			Username:    "Josh",
			Permissions: []string{"view-secret"},
		},
		{
			Username:    "Not Josh",
			Permissions: []string{""},
		},
	}

	layouts, err := template.New("").ParseGlob(webDir + "/template/layouts/*.gotmpl")
	if err != nil {
		log.Fatal(err)
	}

	files, err := filepath.Glob(webDir + "/template/*.gotmpl")
	if err != nil {
		log.Fatal(err)
	}
	tmpls := map[string]*template.Template{}

	for _, file := range files {
		tmpls[filepath.Base(file)] = template.Must(template.Must(layouts.Clone()).ParseFiles(file))
	}

	http.HandleFunc(server.HelloRoute, server.HelloHandler(tmpls["hello.gotmpl"]))
	http.HandleFunc(server.SecretRoute, protect(userDB, server.SecretHandler(tmpls["secret.gotmpl"])))
	http.HandleFunc(server.ListUsersRoute, server.ListUsersHandler(tmpls["list_users.gotmpl"]))

	// e.g. http://localhost:8080/public/hey.txt
	http.Handle("/public/", http.StripPrefix("/public", http.FileServer(http.Dir("web/static"))))

	http.ListenAndServe(":8080", nil)
}

func protect(userDB []User, next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		cookie, err := r.Cookie("login")

		if err == nil {
			for _, user := range userDB {
				if user.Username == cookie.Value {
					ctx := r.Context()

					next(w, r.WithContext(context.WithValue(ctx, "permissions", user.Permissions)))
					return
				}
			}
		}

		http.Error(w, "Nope", http.StatusForbidden)
	}
}
