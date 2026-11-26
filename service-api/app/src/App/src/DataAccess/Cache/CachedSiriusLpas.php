<?php

declare(strict_types=1);

namespace App\DataAccess\Cache;

use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\Response\LpaInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
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
        return $this->lookupAsync($uids)->wait();
    }

    public function lookupAsync(array $uids, ?callable $onLpa = null): PromiseInterface
    {
        $uids = array_values(array_unique($uids));

        $results  = [];
        $promises = [];
        foreach ($uids as $uid) {
            $lpa = $this->cache->get($this->cacheKey($uid));
            if ($lpa instanceof LpaInterface) {
                $results[$uid] = $lpa;
                $promises[]    = $onLpa !== null ? $onLpa($lpa, $uid) : null;
            }
        }

        $missing = array_values(array_diff($uids, array_keys($results)));

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
            $promises[] = $this->siriusLpas
                ->lookupAsync(
                    $missing,
                    function (LpaInterface $lpa, string $uid) use ($onLpa): mixed {
                        $this->cache->set($this->cacheKey($uid), $lpa);

                        return $onLpa !== null ? $onLpa($lpa, $uid) : null;
                    },
                )
                ->then(function (array $lpas) use (&$results): void {
                    $results += $lpas;
                });
        }

        return Utils::all($promises)->then(function () use (&$results): array {
            return $results;
        });
    }

    private function cacheKey(string $uid): string
    {
        return 'sirius-lpa-' . hash('sha256', $uid);
    }
}
