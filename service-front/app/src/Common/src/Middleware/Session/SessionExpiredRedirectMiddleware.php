<?php

declare(strict_types=1);

namespace Common\Middleware\Session;

use Common\Service\Session\EncryptedCookiePersistence;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class SessionExpiredRedirectMiddleware
 *
 * Responsible for redirecting to an appropriate page when the session is loaded as expired. Determination
 * of the expiry of a session is made in the EncryptedCookiePersistence class and attached as data into the session.
 *
 * @package Common\Middleware\Session
 */
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

        // We've already determined that the session has expired at this point.
        if ($session !== null && $session->has(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)) {
            return new RedirectResponse($this->helper->generate('session-expired'));
        } else {
            return $handler->handle($request);
        }
    }
}
