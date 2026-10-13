<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\AuditableLpasInterface;
use App\DataAccess\Repository\Response\LpaInterface;
use App\Entity\Lpa;
use Psr\Log\LoggerInterface;

/**
 * Decorator of DataStoreLpas that holds an in-memory cache of fetched LPAs for the lifetime
 * of the instance.
 *
 * The cache is partitioned by originator id so that each distinct originator still results in
 * an auditable request to the data store.
 */
final class CachedDataStoreLpas implements AuditableLpasInterface
{
    private ?string $originatorId = null;

    /** @var array<string, array<string, LpaInterface>> */
    private array $cache = [];

    public function __construct(
        private readonly DataStoreLpas $dataStoreLpas,
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

        if (isset($this->cache[$this->originatorId][$uid])) {
            $this->logger->debug('Data store LPA cache hit for {lpaUid}', ['lpaUid' => $uid]);

            return $this->cache[$this->originatorId][$uid];
        }

        $this->logger->debug('Data store LPA cache miss for {lpaUid}', ['lpaUid' => $uid]);

        $lpa = $this->dataStoreLpas->get($uid);
        if ($lpa !== null) {
            $this->cache[$this->originatorId][$uid] = $lpa;
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

        $uids   = array_values(array_unique($uids));
        $cached = $this->cache[$this->originatorId] ?? [];

        $missing = array_values(
            array_filter($uids, fn (string $uid): bool => !isset($cached[$uid]))
        );

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
                    $this->cache[$this->originatorId][$data->getUid()] = $lpa;
                }
            }
        }

        $results = [];
        foreach ($uids as $uid) {
            if (isset($this->cache[$this->originatorId][$uid])) {
                $results[] = $this->cache[$this->originatorId][$uid];
            }
        }

        return $results;
    }
}
