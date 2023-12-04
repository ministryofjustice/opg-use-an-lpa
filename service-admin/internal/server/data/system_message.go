package data

import (
	"context"
)

type SystemMessageService struct {
	ssmConn SSMConnection
}

func NewSystemMessageService(ssmConn SSMConnection) *SystemMessageService {
	return &SystemMessageService{ssmConn: ssmConn}
}

func (s *SystemMessageService) GetSystemMessages(ctx context.Context) (metricValues map[string]string, err error) {
	//viewEng, err := conn.ReadParameter("view_eng")
	//viewCy, err := conn.ReadParameter("view_eng")
	//useEng, err := conn.ReadParameter("use_eng")
	//useCy, err := conn.ReadParameter("use_eng")
	return make(map[string]string), nil
}
