<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\CheckDetailsAndConsent;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\LoggerAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Features\FeatureEnabled;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class CheckDetailsAndConsentHandler extends AbstractHandler implements UserAware, CsrfGuardAware, LoggerAware
{
    use User;
    use CsrfGuard;
    use SessionTrait;
    use Logger;

    private ?SessionInterface $session;
    private ?UserInterface $user;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        FeatureEnabled $featureEnabled
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
        $this->featureEnabled = $featureEnabled;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new CheckDetailsAndConsent($this->getCsrfGuard($request));
        $this->user = $this->getUser($request);
        $this->session = $this->getSession($request, 'session');
        $this->identity = (!is_null($this->user)) ? $this->user->getIdentity() : null;

        if (
            is_null($this->session)
            || (is_null($this->session->get('telephone')) || is_null($this->session->get('no_phone')))
        ) {
            throw new SessionTimeoutException();
        }
    }
}
