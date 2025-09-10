<?php

declare(strict_types=1);

namespace Common\Handler;

use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session;
use Common\Handler\Traits\User;
use Common\Service\Session\EncryptedCookiePersistence;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class SessionCheckHandler extends AbstractHandler implements UserAware, SessionAware, LoggerAware
{
    use Logger;
    use Session;
    use User;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private int $sessionTime,
        private int $sessionWarningTime,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user    = $this->getUser($request);
        $session = $this->getSession($request, SessionMiddleware::SESSION_ATTRIBUTE);

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
