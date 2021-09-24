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
use Common\Middleware\Security\UserIdentificationMiddleware;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CreateAccountHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class LogoutPageHandler extends AbstractHandler implements SessionAware, UserAware, LoggerAware
{
    use Session;
    use User;
    use Logger;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authentication,
        LoggerInterface $logger
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authentication);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getUser($request);

        $session = $this->getSession($request, 'session');

        // TODO UML-1758 session clearing hack till we figure out a better way.
        $id = $session->get(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE);
        $session->clear();
        $session->set(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE, $id);

        $session->regenerate();

        $this->getLogger()->info(
            'Account with Id {id} has logged out of the service',
            [
                'id' => $user->getIdentity()
            ]
        );

        return new RedirectResponse('https://www.gov.uk/done/use-lasting-power-of-attorney');
    }
}
