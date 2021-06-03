package server

import (
	"net/http"
	"time"

	"github.com/rs/zerolog/hlog"
	"github.com/rs/zerolog/log"
)

func JsonLogging(next http.Handler) http.Handler {
	logger := hlog.NewHandler(log.Logger)
	access := hlog.AccessHandler(func(r *http.Request, status, size int, duration time.Duration) {
		hlog.FromRequest(r).Info().
			Str("method", r.Method).
			Stringer("url", r.URL).
			Int("status", status).
			Int("size", size).
			Dur("duration", duration).
			Msg("")
	})
	remote := hlog.RemoteAddrHandler("ip")
	userAgent := hlog.UserAgentHandler("user-agent")
	referer := hlog.RefererHandler("referer")

	return logger(
		access(
			remote(
				userAgent(
					referer(
						http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
							next.ServeHTTP(w, r)
						}))))))
}
