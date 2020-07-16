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
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;

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
        $session->unset(UserInterface::class);
        $session->regenerate();

        $this->getLogger()->info(
            'Account with Id {id} has logged out of the service',
            [
                'id' => $user->getIdentity()
            ]
        );

        return $this->redirectToRoute('home');
    }
}
