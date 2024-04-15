<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\LoggerAware;
use Common\Handler\SessionAware;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Authentication\LogoutStrategy;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class LogoutPageHandler extends AbstractHandler implements SessionAware, UserAware, LoggerAware
{
    use Logger;
    use Session;
    use User;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authentication,
        LoggerInterface $logger,
        private LogoutStrategy $logoutStrategy,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authentication);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $this->getSession($request, 'session');
        $user    = $this->getUser($request);

        // Remove the record of our user object from the session. At this point it is still attached to the
        // request as an attribute but as this is not a middleware it passes no further.
        $session?->unset(UserInterface::class);
        $session?->regenerate();

        if ($user !== null) {
            $redirectUrl = $this->logoutStrategy->logout($user);
        }

        $this->getLogger()->info(
            'Account with Id {id} has logged out of the service',
            [
                'id' => $user?->getIdentity(),
            ]
        );

        return new RedirectResponse($redirectUrl ?? $this->urlHelper->generate('home'));
    }
}
