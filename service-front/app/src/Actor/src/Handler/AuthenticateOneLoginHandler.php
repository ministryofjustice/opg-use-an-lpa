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
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class AuthenticateOneLoginHandler extends AbstractHandler implements CsrfGuardAware, LoggerAware, SessionAware
{
    use CsrfGuard;
    use Logger;
    use Session;

    public const OIDC_AUTH_INTERFACE = 'oidcauthinterface';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private OneLoginService $authenticateOneLoginService,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new OneLoginForm($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $url      = $this->urlHelper->generate();
            $uiLocale = (str_contains($url, '/cy/') ? 'cy' : 'en');
            $result   = $this->authenticateOneLoginService->authenticate($uiLocale);
            $this
                ->getSession($request, SessionMiddleware::SESSION_ATTRIBUTE)
                ?->set(self::OIDC_AUTH_INTERFACE, AuthSession::fromArray($result));
            return new RedirectResponse($result['url']);
        }

        return new HtmlResponse($this->renderer->render('actor::one-login', [
            'form' => $form,
        ]));
    }
}