<?php

declare(strict_types=1);

namespace Common\Middleware\Security;

use Common\Service\Log\EventCodes;
use Common\Service\Security\UserIdentification;
use Common\Service\Security\UserIdentificationService;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class UserIdentificationMiddleware
 *
 * Attempts to uniquely identify the user of an application for the purposes of throttling and brute force
 * protection.
 *
 * @package Common\Middleware\Security
 */
class UserIdentificationMiddleware implements MiddlewareInterface
{
    public const IDENTIFY_ATTRIBUTE = 'identity';

    /**
     * @var UserIdentificationService
     */
    private $identificationService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(UserIdentificationService $identificationService, LoggerInterface $logger)
    {
        $this->identificationService = $identificationService;
        $this->logger = $logger;
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
                'identity' => $id
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
                        'event_code' => EventCodes::IDENTITY_HASH_CHANGE,
                        'stored_identity' => $sessionIdentity,
                        'calculated_identity' => $id
                    ]
                );
            }

            $session->set(self::IDENTIFY_ATTRIBUTE, $id);
        }

        return $handler->handle($request->withAttribute(self::IDENTIFY_ATTRIBUTE, $id));
    }
}
