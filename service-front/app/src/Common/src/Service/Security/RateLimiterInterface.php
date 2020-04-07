<?php

declare(strict_types=1);

namespace Common\Service\Security;

use Common\Exception\RateLimitExceededException;

interface RateLimiterInterface
{
    /**
     * Checks to see if a given identity is limited.
     *
     * Calling this function carries out the moving of the ttl window for the rate limit records
     *
     * @param string $identity A unique identity to track
     * @return bool Is the identity limited for the given key
     */
    public function isLimited(string $identity): bool;

    /**
     * Writes a rate limit record for the identity, throws a RateLimitExceededException if this
     * results in the request exceeding the configured limit for this service.
     *
     * @param string $identity A unique identity to limit
     * @throws RateLimitExceededException If a limit check exceeds the configured limit
     */
    public function limit(string $identity): void;

    /**
     * @return string The configuration name of the RateLimiterInterface instance
     */
    public function getName(): string;
}