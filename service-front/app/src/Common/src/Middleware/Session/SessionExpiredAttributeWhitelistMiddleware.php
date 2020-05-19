<?php

declare(strict_types=1);

namespace Common\Middleware\Session;

use Common\Middleware\Security\UserIdentificationMiddleware;
use Common\Service\Session\EncryptedCookiePersistence;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Upon detection of an Expiration value in the session will remove all
 * session values apart from those whitelisted here
 *
 * Used to log a user out, or remove the expired key after other middle wares have had the chance to work
 * on it.
 *
 * @package Common\Middleware\Session
 */
class SessionExpiredAttributeWhitelistMiddleware implements MiddlewareInterface
{
    protected const WHITELIST = [
        UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE,
        EncryptedCookiePersistence::SESSION_TIME_KEY,
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var SessionInterface $session */
        if (null !== $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE)) {
            foreach ($session->toArray() as $key => $value) {
                if (! in_array($key, self::WHITELIST)) {
                    $session->unset($key);
                }
            }
        }

        return $handler->handle($request);
    }
}
