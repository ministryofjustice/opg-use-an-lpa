package server

import (
	"net/http"
	"time"

	"github.com/rs/zerolog"
	"github.com/rs/zerolog/hlog"
)

func WithJSONLogging(next http.Handler, log zerolog.Logger) http.Handler {
	logger := hlog.NewHandler(log)
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
						next)))))
}
