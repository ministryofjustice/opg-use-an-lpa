package handlers

import (
	"io/fs"
	"testing"
	"testing/fstest"

	"github.com/stretchr/testify/assert"
)

func testStaticFS() fs.FS {
	return fstest.MapFS{
		"private.html": {},
		"static/test.html": {
			Data: []byte("<html></html>"),
		},
		"static/css/main.css": {
			Data: []byte("body{color:black;}"),
		},
	}
}

func TestStaticHandler(t *testing.T) {
	t.Parallel()

	tests := []struct {
		name string
		fsys fs.FS
		url  string
		want int
	}{
		{
			name: "protects against directory traversal",
			fsys: testStaticFS(),
			url:  "/css/../../private.html",
			want: 404,
		},
		{
			name: "finds static file in subfolder",
			fsys: testStaticFS(),
			url:  "/css/main.css",
			want: 200,
		},
		{
			name: "finds static file",
			fsys: testStaticFS(),
			url:  "/test.html",
			want: 200,
		},
		{
			name: "does not find missing file",
			fsys: testStaticFS(),
			url:  "/notexist.html",
			want: 404,
		},
	}

	for _, tt := range tests {
		tt := tt

		t.Run(tt.name, func(t *testing.T) {
			t.Parallel()

			tp, err := fs.Sub(tt.fsys, "static")
			if err != nil {
				t.Fatalf("unable to create sub filesystem, %v", err)
			}

			h := StaticHandler(tp)

			assert.HTTPStatusCode(t, h, "GET", tt.url, nil, tt.want)
		})
	}
}
