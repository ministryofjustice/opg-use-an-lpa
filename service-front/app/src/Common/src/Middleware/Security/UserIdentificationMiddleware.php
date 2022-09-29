<?php

declare(strict_types=1);

namespace Common\Middleware\Security;

use Common\Service\Log\EventCodes;
use Common\Service\Security\UserIdentificationService;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Attempts to uniquely identify the user of an application for the purposes of throttling and brute force
 * protection.
 */
class UserIdentificationMiddleware implements MiddlewareInterface
{
    public const IDENTIFY_ATTRIBUTE = 'identity';

    public function __construct(private UserIdentificationService $identificationService, private LoggerInterface $logger)
    {
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $id = $this->identificationService->id($request);

        $this->logger->debug(
            'Identity of incoming request is {identity}',
            [
                'identity' => $id,
            ]
        );

        /** @var SessionInterface $session */
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        if ($session !== null) {
            // if the identity in the session does not match the identity we just calculated something about this
            // request is probably nefarious, log it.
            $sessionIdentity = $session->get(self::IDENTIFY_ATTRIBUTE);
            if ($sessionIdentity !== null && $sessionIdentity !== $id) {
                $this->logger->notice(
                    'Identity of incoming request is different to session stored identity',
                    [
                        'event_code'          => EventCodes::IDENTITY_HASH_CHANGE,
                        'stored_identity'     => $sessionIdentity,
                        'calculated_identity' => $id,
                    ]
                );
            }

            $session->set(self::IDENTIFY_ATTRIBUTE, $id);
        }

        return $handler->handle($request->withAttribute(self::IDENTIFY_ATTRIBUTE, $id));
    }
}
