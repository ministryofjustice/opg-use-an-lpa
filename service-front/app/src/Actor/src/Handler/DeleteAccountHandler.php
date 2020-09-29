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
use Common\Service\Log\Output\Email;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Common\Service\User\UserService;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Class DeleteAccountHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class DeleteAccountHandler extends AbstractHandler implements SessionAware, UserAware, LoggerAware
{
    use Session;
    use User;
    use Logger;

    /** @var UserService */
    private $userService;

    /**
     * DeleteAccountHandler constructor
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UserService $userService
     * @param LoggerInterface $logger
     *
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authentication,
        UserService $userService,
        LoggerInterface $logger
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->userService = $userService;
        $this->setAuthenticator($authentication);
    }

    /**
     * @param ServerRequestInterface $request
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
