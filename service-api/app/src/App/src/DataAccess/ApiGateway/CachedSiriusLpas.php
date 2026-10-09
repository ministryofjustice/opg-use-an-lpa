<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\Response\LpaInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Decorator of SiriusLpas that caches fetched LPAs for the lifetime of the request.
 */
final class CachedSiriusLpas implements LpasInterface
{
    public function __construct(
        private readonly SiriusLpas $siriusLpas,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function get(string $uid): ?LpaInterface
    {
        $cacheKey  = $this->cacheKey($uid);
        $cachedLpa = $this->cache->get($cacheKey);
        if ($cachedLpa instanceof LpaInterface) {
            $this->logger->debug('Sirius LPA cache hit for {lpaUid}', ['lpaUid' => $uid]);

            return $cachedLpa;
        }

        $this->logger->debug('Sirius LPA cache miss for {lpaUid}', ['lpaUid' => $uid]);

        $lpa = $this->siriusLpas->get($uid);
        if ($lpa !== null) {
            $this->cache->set($cacheKey, $lpa);
        }

        return $lpa;
    }

    public function lookup(array $uids): array
    {
        $uids = array_values(array_unique($uids));

        $cached = [];
        foreach ($uids as $uid) {
            $lpa = $this->cache->get($this->cacheKey($uid));
            if ($lpa instanceof LpaInterface) {
                $cached[$uid] = $lpa;
            }
        }

        $missing = array_values(array_diff($uids, array_keys($cached)));

        $this->logger->debug(
            'Sirius LPA cache lookup of {count} LPAs had {hits} hits and {misses} misses',
            [
                'count'  => count($uids),
                'hits'   => count($uids) - count($missing),
                'misses' => count($missing),
                'missed' => $missing,
            ],
        );

        if ($missing !== []) {
            foreach ($this->siriusLpas->lookup($missing) as $uid => $lpa) {
                $uid          = (string) $uid;
                $cached[$uid] = $lpa;
                $this->cache->set($this->cacheKey($uid), $lpa);
            }
        }

        $results = [];
        foreach ($uids as $uid) {
            if (array_key_exists($uid, $cached)) {
                $results[$uid] = $cached[$uid];
            }
        }

        return $results;
    }

    private function cacheKey(string $uid): string
    {
        return 'sirius-lpa-' . hash('sha256', $uid);
    }
}
