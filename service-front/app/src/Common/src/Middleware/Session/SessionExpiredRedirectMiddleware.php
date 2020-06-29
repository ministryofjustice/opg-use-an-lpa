<?php

declare(strict_types=1);

namespace Common\Middleware\Session;

use Common\Service\Session\EncryptedCookiePersistence;
use Laminas\Diactoros\Uri;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Router\RouterInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionExpiredRedirectMiddleware implements MiddlewareInterface
{
    /**
     * @var ServerUrlHelper
     */
    private $helper;

    public function __construct(ServerUrlHelper $helper)
    {
        $this->helper = $helper;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var SessionInterface $session */
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        if ($session !== null && $session->get(EncryptedCookiePersistence::SESSION_EXPIRED_KEY) !== null) {
            $session->unset(EncryptedCookiePersistence::SESSION_EXPIRED_KEY);
            $uri = new Uri($this->helper->generate('/session-expired'));

            return $handler->handle($request->withUri($uri));
        } else {
            return $handler->handle($request);
        }
    }
}
