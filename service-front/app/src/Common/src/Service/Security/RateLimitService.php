<?php

declare(strict_types=1);

namespace Common\Service\Security;

use Laminas\Cache\Storage\StorageInterface;
use Psr\Log\LoggerInterface;

abstract class RateLimitService implements RateLimiterInterface
{
    /**
     * @var StorageInterface
     */
    protected $cacheService;

    /**
     * @var int
     */
    protected $interval;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var int
     */
    protected $requestsPerInterval;

    public function __construct(StorageInterface $cacheService, int $interval,  int $requestsPerInterval, LoggerInterface $logger)
    {
        $this->cacheService = $cacheService;
        $this->interval = $interval;
        $this->requestsPerInterval = $requestsPerInterval;
        $this->logger = $logger;
    }

    abstract function isLimited(string $identity): bool;

    abstract function limit(string $identity): void;

    public function getName(): string
    {
        return $this->cacheService->getOptions()->getNamespace();
    }
}