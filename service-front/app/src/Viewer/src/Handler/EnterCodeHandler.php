<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Service\SystemMessage\SystemMessageService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viewer\Form\ShareCode;

class EnterCodeHandler extends AbstractHandler implements CsrfGuardAware
{
    use CsrfGuard;
    use SessionTrait;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private SystemMessageService $systemMessageService,
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $this->getSession($request, 'session');

        $form = new ShareCode($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $lpaCode = $form->getData()['lpa_code'];

                $session->set('code', $lpaCode);
                $session->set('surname', $form->getData()['donor_surname']);

                return $this->redirectToRoute('check-code');
            }
        }

        $systemMessages = $this->systemMessageService->getMessages();

        return new HtmlResponse($this->renderer->render('viewer::enter-code', [
            'form'       => $form,
            'en_message' => $systemMessages['view/en'] ?? null,
            'cy_message' => $systemMessages['view/cy'] ?? null,
        ]));
    }
}
