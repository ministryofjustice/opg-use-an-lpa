package time

import "time"

type ServerTime struct{}

func (st *ServerTime) Now() time.Time {
	return time.Now()
}
