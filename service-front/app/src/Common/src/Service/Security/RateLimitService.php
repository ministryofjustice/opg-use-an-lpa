<?php

declare(strict_types=1);

namespace Common\Service\Security;

use Laminas\Cache\Storage\StorageInterface;
use Psr\Log\LoggerInterface;

abstract class RateLimitService implements RateLimiterInterface
{
    public function __construct(
        protected StorageInterface $cacheService,
        protected int $interval,
        protected int $requestsPerInterval,
        protected LoggerInterface $logger,
    ) {
    }

    abstract public function isLimited(string $identity): bool;

    abstract public function limit(string $identity): void;

    public function getName(): string
    {
        return $this->cacheService->getOptions()->getNamespace();
    }
}
