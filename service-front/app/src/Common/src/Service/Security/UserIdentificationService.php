<?php

declare(strict_types=1);

namespace Common\Service\Security;

use Common\Service\Log\EventCodes;
use Psr\Log\LoggerInterface;

class UserIdentificationService
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * Builds a unique userId that can be used to identify users for security tracking.
     *
     * @param  array<string, array<array-key, string>> $headers
     * @param  string|null                             $sessionIdentity
     * @return UserIdentity
     */
    public function id(array $headers, ?string $sessionIdentity): UserIdentity
    {
        $id = new UserIdentity(
            $this->getHeader($headers, 'accept'),
            $this->getHeader($headers, 'accept-encoding'),
            $this->getHeader($headers, 'accept-language'),
            $this->getHeader($headers, 'user-agent'),
            $this->getHeader($headers, 'x-forwarded-for')
        );

        $this->logger->debug(
            'Identity of incoming request is {identity}',
            [
                'identity' => $id,
                'data'     => $id->data,
            ]
        );

        // if the identity in the session does not match the identity we just calculated something about this
        // request is probably nefarious, log it.
        if ($sessionIdentity !== null && $sessionIdentity !== $id->hash()) {
            $this->logger->notice(
                'Identity of incoming request is different to session stored identity',
                [
                    'event_code'          => EventCodes::IDENTITY_HASH_CHANGE,
                    'stored_identity'     => $sessionIdentity,
                    'calculated_identity' => $id,
                    'data'                => $id->data,
                ]
            );
        }

        return $id;
    }

    private function getHeader(array $headers, string $headerName): string
    {
        return array_key_exists($headerName, $headers)
            ? $headers[$headerName][0]
            : '';
    }
}
