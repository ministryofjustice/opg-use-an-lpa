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
use Psr\Log\LoggerInterface;

/**
 * Upon detection of an Expiration value in the session will remove all
 * session values apart from those listed here
 *
 * Used to log a user out, or remove the expired key after other middle wares have had the chance to work
 * on it.
 *
 * @package Common\Middleware\Session
 */
class SessionExpiredAttributeAllowlistMiddleware implements MiddlewareInterface
{
    /**
     * An array of allowed session keys that can persist across session expiry
     */
    public const ALLOWLIST = [
        UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE,
        EncryptedCookiePersistence::SESSION_EXPIRED_KEY,
    ];

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Process the request as normal
        $response = $handler->handle($request);

        // Ensure that we strip out any session information that shouldn't be in there
        // if the session has expired.
        /** @var SessionInterface $session */
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        if ($session !== null && $session->has(EncryptedCookiePersistence::SESSION_EXPIRED_KEY)) {
            $this->stripSession($session);

            // TODO: UML-1449 logs incorrect time value as seconds should equal time() - TIME_KEY + Session Length
            $this->logger->info(
                'User session expired approx {seconds} seconds ago',
                [
                    'seconds' => time() - $session->get(EncryptedCookiePersistence::SESSION_TIME_KEY)
                ]
            );
        }

        return $response;
    }

    private function stripSession(SessionInterface $session)
    {
        foreach ($session->toArray() as $key => $value) {
            if (! in_array($key, self::ALLOWLIST)) {
                $session->unset($key);
            }
        }
    }
}
