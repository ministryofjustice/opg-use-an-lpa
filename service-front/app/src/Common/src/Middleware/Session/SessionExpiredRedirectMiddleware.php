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
use DateTime;

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

        if ($session !== null && $session->has(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)) {

            $sessionExpiredDatetime = $session->get(EncryptedCookiePersistence::SESSION_TIME_KEY);

            $currentDate = (new DateTime())->format('Y-m-d');
            $expiredDate = (new DateTime("@$sessionExpiredDatetime"))->format('Y-m-d');

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
