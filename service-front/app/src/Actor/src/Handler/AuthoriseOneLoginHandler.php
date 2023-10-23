<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\OneLoginForm;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\LoggerAware;
use Common\Handler\SessionAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session;
use Common\Service\OneLogin\OneLoginService;
use Facile\OpenIDClient\Session\AuthSession;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class AuthoriseOneLoginHandler extends AbstractHandler implements CsrfGuardAware, LoggerAware, SessionAware
{
    use CsrfGuard;
    use Logger;
    use Session;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private OneLoginService $authoriseOneLoginService,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new OneLoginForm($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $this->getLogger()->info('SUBMIT PRESSED');
            $url                  = $this->urlHelper->generate();
            $uiLocale             = (str_contains($url, '/cy/') ? 'cy' : 'en');
            $result               = $this->authoriseOneLoginService->authorise($uiLocale);
            $authSessionInterface = AuthSession::fromArray($result);
        }

        return new HtmlResponse($this->renderer->render('actor::one-login', [
            'form' => $form,
        ]));
    }
}
