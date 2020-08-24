<?php

declare(strict_types=1);

namespace Common\Middleware\Session;

use Common\Service\Session\EncryptedCookiePersistence;
use Laminas\Diactoros\Uri;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\RedirectResponse;

class SessionExpiredRedirectMiddleware implements MiddlewareInterface
{
    /**
     * @var UrlHelper
     */
    private $helper;

    public function __construct(UrlHelper $urlHelper)
    {
        $this->helper = $urlHelper;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var SessionInterface $session */
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $currentDatetime =  time();

        if ($session !== null) {
            $sessionExpiredDatetime = $session->get(EncryptedCookiePersistence::SESSION_TIME_KEY);
        }
        
        if ($session !== null && $session->get(EncryptedCookiePersistence::SESSION_EXPIRED_KEY) !== null) {

            $currentDate = date("Y-m-d", $currentDatetime);
            $expiredDate = date("Y-m-d", $sessionExpiredDatetime);

            $session->unset(EncryptedCookiePersistence::SESSION_EXPIRED_KEY);
            if ($currentDate > $expiredDate) {
                $uri = new Uri($this->helper->generate('home'));
            } else {
                $uri = new Uri($this->helper->generate('session-expired'));
            }

            return new RedirectResponse($uri);
        } else {
            return $handler->handle($request);
        }
    }
}
