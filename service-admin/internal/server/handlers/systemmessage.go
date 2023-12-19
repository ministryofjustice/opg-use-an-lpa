package handlers

import (
	"context"
	"github.com/rs/zerolog/log"
	"net/http"
)

type SystemMessageService interface {
	GetSystemMessages(ctx context.Context) (systemMessages map[string]string, err error)
	PutSystemMessages(ctx context.Context, messages map[string]string) (err error)
}

type SystemMessageServer struct {
	systemMessageService SystemMessageService
	templateService      TemplateWriterService
}

func NewSystemMessageServer(systemMessageService SystemMessageService, templateWriterService TemplateWriterService) *SystemMessageServer {
	return &SystemMessageServer{
		systemMessageService: systemMessageService,
		templateService:      templateWriterService,
	}
}

func (s *SystemMessageServer) SystemMessageHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method == "POST" {
		err := r.ParseForm()
		if err != nil {
			log.Error().Err(err).Msg("failed to parse form input")
		}
	}

	// TODO line below will hardcode messages - can delete this line once we can write to param store from the submit button

	//messages := map[string]string{"system-message-use-en": "use hello world en", "system-message-use-cy": "use helo byd",
	//	"system-message-view-en": "view hello world", "system-message-view-cy": "view helo byd"}

	messages, _ := s.systemMessageService.GetSystemMessages(context.Background())

	if err := s.templateService.RenderTemplate(w, r.Context(), "systemmessage.page.gohtml", messages); err != nil {
		log.Panic().Err(err).Msg(err.Error())
	}
}
