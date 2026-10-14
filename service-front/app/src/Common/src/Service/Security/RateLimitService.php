<?php

declare(strict_types=1);

namespace Common\Service\Security;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

abstract class RateLimitService implements RateLimiterInterface
{
    public function __construct(
        protected CacheItemPoolInterface $cacheService,
        protected string $name,
        protected int $interval,
        protected int $requestsPerInterval,
        protected LoggerInterface $logger,
    ) {
    }

    abstract public function isLimited(string $identity): bool;

    abstract public function limit(string $identity): void;

    public function getName(): string
    {
        return $this->name;
    }
}
