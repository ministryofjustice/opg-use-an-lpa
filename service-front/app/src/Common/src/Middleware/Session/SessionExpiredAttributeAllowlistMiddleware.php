<?php

declare(strict_types=1);

namespace Common\Middleware\Session;

use Common\Middleware\Security\UserIdentificationMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
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
 */
class SessionExpiredAttributeAllowlistMiddleware implements MiddlewareInterface
{
    /**
     * An array of allowed session keys that can persist across session expiry
     */
    public const ALLOWLIST = [
        UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE,
        SessionExpiryMiddleware::SESSION_EXPIRED_KEY,
        FlashMessagesInterface::FLASH_NEXT,
    ];

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Ensure that we strip out any session information that shouldn't be in there
        // if the session has expired.
        /** @var SessionInterface|null $session */
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if ($session?->has(SessionExpiryMiddleware::SESSION_EXPIRED_KEY)) {
            // TODO: UML-1449 logs incorrect time value as seconds should equal time() - TIME_KEY + Session Length
            $this->logger->info(
                'User session expired approx {seconds} seconds ago',
                [
                    'seconds' => time() - $session->get(SessionExpiryMiddleware::SESSION_TIME_KEY),
                ]
            );

            $this->stripSession($session);
        }

        return $handler->handle($request);
    }

    private function stripSession(SessionInterface $session): void
    {
        foreach ($session->toArray() as $key => $value) {
            if (! in_array($key, self::ALLOWLIST)) {
                $session->unset($key);
            }
        }
    }
}
