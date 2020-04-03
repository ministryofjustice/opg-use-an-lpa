<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Common\Service\User\UserService;
use Psr\Log\LoggerInterface;

/**
 * Class DeleteAccountHandler
 * @package Actor\Handler
 */
class DeleteAccountHandler extends AbstractHandler implements CsrfGuardAware
{
    use CsrfGuard;

    /** @var UserService */
    private $userService;

    /**
     * DeleteAccountHandler constructor
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UserService $userService
     * @param LoggerInterface $logger
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UserService $userService,
        LoggerInterface $logger
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->userService = $userService;
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $accountId = $request->getParsedBody()['account_id'];
        $email = $request->getParsedBody()['user_email'];

        $user = $this->userService->getByEmail($email);

        if ($user['Email'] !== $email) {
            throw new Exception('User email does not match the form email');
        }

        if ($user['Id'] !== $accountId) {
            throw new Exception('User account Id does not match the form account Id');
        }

        $this->userService->deleteAccount($accountId, $email);
    }
}
