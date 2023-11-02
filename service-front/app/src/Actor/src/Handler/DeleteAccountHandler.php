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
use Common\Service\User\UserService;
use Exception;
use Laminas\Diactoros\Response\HtmlResponse;
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
class DeleteAccountHandler extends AbstractHandler implements SessionAware, UserAware, LoggerAware
{
    use Logger;
    use Session;
    use User;

    /**
     * DeleteAccountHandler constructor
     *
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper                 $urlHelper
     * @param UserService               $userService
     * @param LoggerInterface           $logger
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authentication,
        private UserService $userService,
        LoggerInterface $logger,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authentication);
    }

    /**
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getUser($request);

        $this->userService->deleteAccount($user->getIdentity());

        $session = $this->getSession($request, 'session');
        $session->unset(UserInterface::class);
        $session->regenerate();

        return new HtmlResponse($this->renderer->render('actor::deleted-account-confirmation'));
    }
}
