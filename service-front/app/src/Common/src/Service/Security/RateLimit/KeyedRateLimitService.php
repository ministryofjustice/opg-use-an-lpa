<?php

declare(strict_types=1);

namespace Common\Service\Security\RateLimit;

use Common\Exception\RateLimitExceededException;
use Common\Service\Security\RateLimitService;
use Psr\Cache\InvalidArgumentException;

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
     * @return bool Is the identity limited for the given key
     * @throws InvalidArgumentException
     */
    public function isLimited(string $identity, string $key = ''): bool
    {
        $recordKey = $this->getRecordKey($identity, $key);

        $cacheItem = $this->cacheService->getItem($recordKey);
        if (!$cacheItem->isHit()) {
            return false;
        }

        /** @var array<int, int> $accessRecords */
        $accessRecords = $cacheItem->get();

        // walk the time window and drop expired records
        $expiredTime   = time() - $this->interval;
        $accessRecords = array_filter($accessRecords, function (int $item) use ($expiredTime) {
            return $item >= $expiredTime;
        });

        // update the window
        $cacheItem->set($accessRecords);
        $this->cacheService->save($cacheItem);

        return count($accessRecords) > $this->requestsPerInterval;
    }

    /**
     * Writes a rate limit record for the identity and key, throws a RateLimitExceededException if this
     * results in the request exceeding the configured limit for this service.
     *
     * @param string $identity A unique identity to limit
     * @param string $key      An optional piece of information to key this limit for
     * @throws RateLimitExceededException If a limit check exceeds the configured limit
     * @throws InvalidArgumentException
     */
    public function limit(string $identity, string $key = ''): void
    {
        $recordKey = $this->getRecordKey($identity, $key);

        $cacheItem = $this->cacheService->getItem($recordKey);
        /** @var array<int, int> $accessRecords */
        $accessRecords = $cacheItem->isHit() ? $cacheItem->get() : [];

        // add our record
        $accessRecords[] = time();
        $cacheItem->set($accessRecords);
        $this->cacheService->save($cacheItem);

        // throw exception if rate limit is exceeded
        if (count($accessRecords) > $this->requestsPerInterval) {
            throw new RateLimitExceededException(
                $this->getName() . ' rate limit exceeded for identity ' . $identity
            );
        }
    }

    private function getRecordKey(string $identity, string $key): string
    {
        return hash('sha256', $this->getName() . ':' . $identity . ':' . $key);
    }
}
