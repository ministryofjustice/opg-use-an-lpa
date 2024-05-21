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
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\OneLogin\OneLoginService;
use Facile\OpenIDClient\Session\AuthSession;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\AuthenticationInterface;
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
class AuthenticateOneLoginHandler extends AbstractHandler implements CsrfGuardAware, LoggerAware, SessionAware, UserAware
{
    use CsrfGuard;
    use Logger;
    use Session;
    use User;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        AuthenticationInterface $authenticator,
        private OneLoginService $authenticateOneLoginService,
        private ServerUrlHelper $serverUrlHelper,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!is_null($this->getUser($request))) {
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
                ?->set(OneLoginService::OIDC_AUTH_INTERFACE, AuthSession::fromArray($result));

            return new RedirectResponse($result['url']);
        }

        $params = $request->getQueryParams();
        if (array_key_exists('error', $params)) {
            $form->addErrorMessage($params['error']);
        }

        return new HtmlResponse($this->renderer->render('actor::one-login', [
            'form' => $form,
        ]));
    }
}
