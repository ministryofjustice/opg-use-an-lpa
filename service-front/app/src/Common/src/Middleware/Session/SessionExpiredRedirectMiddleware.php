<?php

declare(strict_types=1);

namespace Common\Middleware\Session;

use Common\Service\Session\EncryptedCookiePersistence;
use Laminas\Diactoros\Uri;
use Mezzio\Helper\ServerUrlHelper;
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

            return new RedirectResponse($uri);
        } else {
            return $handler->handle($request);
        }
    }
}
