<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\AuditableLpasInterface;
use App\DataAccess\Repository\Response\LpaInterface;
use App\Entity\Lpa;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Decorator of DataStoreLpas that caches fetched LPAs for the lifetime of the request.
 *
 * The cache is partitioned by originator id so that each distinct originator still results in
 * an auditable request to the data store.
 */
final class CachedDataStoreLpas implements AuditableLpasInterface
{
    private ?string $originatorId = null;

    public function __construct(
        private readonly DataStoreLpas $dataStoreLpas,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function setOriginatorId(string $originatorId): AuditableLpasInterface
    {
        $this->originatorId = $originatorId;
        $this->dataStoreLpas->setOriginatorId($originatorId);

        return $this;
    }

    public function get(string $uid): ?LpaInterface
    {
        if ($this->originatorId === null) {
            $this->logger->debug(
                'Data store LPA cache bypassed for {lpaUid} as no originator id is set',
                ['lpaUid' => $uid],
            );

            return $this->dataStoreLpas->get($uid);
        }

        $originatorId = $this->originatorId;
        $cacheKey     = $this->cacheKey($originatorId, $uid);
        $cachedLpa    = $this->cache->get($cacheKey);
        if ($cachedLpa instanceof LpaInterface) {
            $this->logger->debug('Data store LPA cache hit for {lpaUid}', ['lpaUid' => $uid]);

            return $cachedLpa;
        }

        $this->logger->debug('Data store LPA cache miss for {lpaUid}', ['lpaUid' => $uid]);

        $lpa = $this->dataStoreLpas->get($uid);
        if ($lpa !== null) {
            $this->cache->set($cacheKey, $lpa);
        }

        return $lpa;
    }

    public function lookup(array $uids): array
    {
        if ($this->originatorId === null) {
            $this->logger->debug(
                'Data store LPA cache bypassed for lookup of {count} LPAs as no originator id is set',
                ['count' => count($uids)],
            );

            return $this->dataStoreLpas->lookup($uids);
        }

        $uids         = array_values(array_unique($uids));
        $originatorId = $this->originatorId;
        $cached       = [];
        foreach ($uids as $uid) {
            $lpa = $this->cache->get($this->cacheKey($originatorId, $uid));
            if ($lpa instanceof LpaInterface) {
                $cached[$uid] = $lpa;
            }
        }

        $missing = array_values(array_diff($uids, array_keys($cached)));

        $this->logger->debug(
            'Data store LPA cache lookup of {count} LPAs had {hits} hits and {misses} misses',
            [
                'count'  => count($uids),
                'hits'   => count($uids) - count($missing),
                'misses' => count($missing),
                'missed' => $missing,
            ],
        );

        if ($missing !== []) {
            foreach ($this->dataStoreLpas->lookup($missing) as $lpa) {
                $data = $lpa->getData();
                if ($data instanceof Lpa) {
                    $uid          = $data->getUid();
                    $cached[$uid] = $lpa;
                    $this->cache->set($this->cacheKey($originatorId, $uid), $lpa);
                }
            }
        }

        $results = [];
        foreach ($uids as $uid) {
            if (isset($cached[$uid])) {
                $results[] = $cached[$uid];
            }
        }

        return $results;
    }

    private function cacheKey(string $originatorId, string $uid): string
    {
        return 'data-store-lpa-' . hash('sha256', $originatorId) . '-' . hash('sha256', $uid);
    }
}
