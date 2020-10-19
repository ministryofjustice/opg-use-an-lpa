<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\LoggerAware;
use Common\Handler\SessionAware;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Session\EncryptedCookiePersistence;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ViewerSessionCheckHandler
 * @package Viewer\Handler
 * @codeCoverageIgnore
 * Tests are covered by ActorSessionCheckHandler
 */
class ViewerSessionCheckHandler extends AbstractHandler implements UserAware, SessionAware, LoggerAware
{
    use User;
    use Session;
    use Logger;

    private int $sessionTime;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        LoggerInterface $logger,
        UrlHelper $urlHelper,
        int $sessionTime
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
        $this->sessionTime = $sessionTime;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getUser($request);
        $session = $this->getSession($request, 'session');

        $expiresAt = $session->get(EncryptedCookiePersistence::SESSION_TIME_KEY) + $this->sessionTime;
        $timeRemaining = $expiresAt - time();
        $sessionWarning = false;

        if ($user !== null && $timeRemaining <= 300) {
            $sessionWarning = true;
        }

        return new JsonResponse(
            [
                'session_warning' => $sessionWarning,
                'time_remaining'  => $timeRemaining
            ],
            201
        );
    }
}
