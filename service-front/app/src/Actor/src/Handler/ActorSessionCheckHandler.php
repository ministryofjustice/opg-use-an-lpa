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
use Common\Service\Session\EncryptedCookiePersistence;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ActorSessionCheckHandler extends AbstractHandler implements UserAware, SessionAware, LoggerAware
{
    use Logger;
    use Session;
    use User;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        LoggerInterface $logger,
        UrlHelper $urlHelper,
        private int $sessionTime,
        private int $sessionWarningTime,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user    = $this->getUser($request);
        $session = $this->getSession($request, 'session');

        $expiresAt          = $session->get(EncryptedCookiePersistence::SESSION_TIME_KEY) + $this->sessionTime;
        $timeRemaining      = $expiresAt - time();
        $showSessionWarning = false;

        if ($user !== null && $timeRemaining <= $this->sessionWarningTime) {
            $showSessionWarning = true;
        }

        return new JsonResponse(
            [
                'session_warning' => $showSessionWarning,
                'time_remaining'  => $timeRemaining,
            ]
        );
    }
}
