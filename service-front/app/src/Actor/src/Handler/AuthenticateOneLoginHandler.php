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
use Common\Service\OneLogin\AuthenticationData;
use Common\Service\OneLogin\OneLoginService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Locale;

/**
 * @codeCoverageIgnore
 */
class AuthenticateOneLoginHandler extends AbstractHandler implements CsrfGuardAware, LoggerAware, SessionAware
{
    use CsrfGuard;
    use Logger;
    use Session;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private OneLoginService $authenticateOneLoginService,
        private ServerUrlHelper $serverUrlHelper,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->isUserLoggedIn($request)) {
            return $this->redirectToRoute('lpa.dashboard');
        }

        $form = new OneLoginForm($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $signInLink = $this->serverUrlHelper->generate($this->urlHelper->generate('auth-redirect'));
            $uiLocale   = Locale::getPrimaryLanguage($request->getAttribute('locale'));

            //TODO: UML-3203 Identify if OneLogin can handle multiple redirect urls, then remove
            if ($uiLocale === 'cy') {
                $signInLink = str_replace('/cy', '', $signInLink);
            }

            $result            = $this->authenticateOneLoginService->authenticate($uiLocale, $signInLink);
            $result['customs'] = [
                'ui_locale'    => $uiLocale,
                'redirect_uri' => $signInLink,
            ];

            $this
                ->getSession($request, SessionMiddleware::SESSION_ATTRIBUTE)
                ?->set(OneLoginService::OIDC_AUTH_INTERFACE, AuthenticationData::fromArray($result));

            return new RedirectResponse($result['url']);
        }

        $toRender = ['form' => $form];
        $params   = $request->getQueryParams();
        if (array_key_exists('error', $params)) {
            $toRender['one_login_error'] = $params['error'];
        }

        return new HtmlResponse($this->renderer->render('actor::one-login', $toRender));
    }

    public function isUserLoggedIn(ServerRequestInterface $request): bool
    {
        $userInfo = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)?->get(UserInterface::class);

        return $userInfo !== null;
    }
}
