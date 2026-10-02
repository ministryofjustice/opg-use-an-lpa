<?php

declare(strict_types=1);

namespace App\DataAccess\ApiGateway;

use App\DataAccess\Repository\LpasInterface;
use App\DataAccess\Repository\Response\LpaInterface;

/**
 * Decorator of SiriusLpas that holds an in-memory cache of fetched LPAs for the lifetime
 * of the instance.
 */
class CachedSiriusLpas implements LpasInterface
{
    /** @var array<string, LpaInterface> */
    private array $cache = [];

    public function __construct(private readonly SiriusLpas $siriusLpas)
    {
    }

    public function get(string $uid): ?LpaInterface
    {
        if (array_key_exists($uid, $this->cache)) {
            return $this->cache[$uid];
        }

        $lpa = $this->siriusLpas->get($uid);
        if ($lpa !== null) {
            $this->cache[$uid] = $lpa;
        }

        return $lpa;
    }

    public function lookup(array $uids): array
    {
        $uids = array_values(array_unique($uids));

        $missing = array_values(
            array_filter($uids, fn (string $uid): bool => !array_key_exists($uid, $this->cache))
        );

        if ($missing !== []) {
            foreach ($this->siriusLpas->lookup($missing) as $uid => $lpa) {
                $this->cache[(string) $uid] = $lpa;
            }
        }

        $results = [];
        foreach ($uids as $uid) {
            if (array_key_exists($uid, $this->cache)) {
                $results[$uid] = $this->cache[$uid];
            }
        }

        return $results;
    }
}
