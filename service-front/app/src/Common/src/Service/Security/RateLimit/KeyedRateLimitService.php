<?php

declare(strict_types=1);

namespace Common\Service\Security\RateLimit;

use Common\Exception\RateLimitExceededException;
use Common\Service\Security\RateLimitService;

use function time;

class KeyedRateLimitService extends RateLimitService
{
    /**
     * Checks to see if a given identity is limited for the provided key.
     *
     * Calling this function carries out the moving of the ttl window for the rate limit records
     *
     * @param string $identity A unique identity to track
     * @param string $key      An optional piece of information to key this limit for
     *
     * @return bool Is the identity limited for the given key
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function isLimited(string $identity, string $key = ''): bool
    {
        $recordKey = $this->getRecordKey($identity, $key);

        /** @var array $accessRecords */
        if (null === $accessRecords = $this->cacheService->getItem($recordKey)) {
            return false;
        }

        // walk the time window and drop expired records
        $expiredTime = time() - $this->interval;
        $accessRecords = array_filter($accessRecords, function ($item) use ($expiredTime) {
            return $item >= $expiredTime;
        });

        // update the window
        $this->cacheService->setItem($recordKey, $accessRecords);

        return count($accessRecords) > $this->requestsPerInterval;
    }

    /**
     * Writes a rate limit record for the identity and key, throws a RateLimitExceededException if this
     * results in the request exceeding the configured limit for this service.
     *
     * @param string $identity A unique identity to limit
     * @param string $key      An optional piece of information to key this limit for
     *
     * @throws RateLimitExceededException If a limit check exceeds the configured limit
     * @throws \Laminas\Cache\Exception\ExceptionInterface
     */
    public function limit(string $identity, string $key = ''): void
    {
        $recordKey = $this->getRecordKey($identity, $key);

        /** @var array $accessRecords */
        $accessRecords = $this->cacheService->getItem($recordKey) ?: [];

        // add our record
        $accessRecords[] = time();
        $this->cacheService->setItem($recordKey, $accessRecords);

        // throw exception if rate limit is exceeded
        if (count($accessRecords) > $this->requestsPerInterval) {
            throw new RateLimitExceededException($this->getName() . ' rate limit exceeded for identity ' . $identity);
        }
    }

    private function getRecordKey(string $identity, string $key): string
    {
        return $identity . ':' . $key;
    }
}
