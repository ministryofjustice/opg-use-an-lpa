<?php

declare(strict_types=1);

namespace App\DataAccess\Cache;

use App\DataAccess\Repository\Response\Lpa;
use App\DataAccess\Repository\Response\LpaInterface;
use App\Entity\Lpa as LpaData;
use App\Service\Cache\CacheFactory;
use App\Service\Lpa\SiriusLpa;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use RuntimeException;
use UnexpectedValueException;

final class SessionLpaCache implements SessionLpaCacheInterface
{
    public function __construct(
        private readonly CacheFactory $cacheFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param string[] $uids
     * @param callable(list<string>): array<LpaInterface<LpaData>|LpaInterface<SiriusLpa>> $lookup
     * @return array<LpaInterface<LpaData>|LpaInterface<SiriusLpa>>
     */
    public function lookup(array $uids, ?string $sessionId, callable $lookup): array
    {
        if ($uids === []) {
            return [];
        }

        if ($sessionId === null || $sessionId === '') {
            return $lookup(array_values($uids));
        }

        $cache = $this->cacheFactory->taggable('lpa-in-session');
        [
            $results,
            $missing,
        ]      = $this->fromCache($cache, $sessionId, $uids);

        if ($missing !== []) {
            foreach ($lookup($missing) as $lpa) {
                $data          = $lpa->getData() ?? throw new UnexpectedValueException('Missing LPA data');
                $uid           = $data->getUid();
                $results[$uid] = $lpa;

                $this->store($cache, $sessionId, $uid, $lpa);
            }
        }

        return $results;
    }

    public function lookupAsync(
        array $uids,
        ?string $sessionId,
        callable $lookup,
        ?callable $onLpa = null,
    ): PromiseInterface {
        if ($uids === []) {
            return Create::promiseFor([]);
        }

        if ($sessionId === null || $sessionId === '') {
            return $lookup(array_values($uids), $onLpa);
        }

        $cache = $this->cacheFactory->taggable('lpa-in-session');
        [
            $results,
            $missing,
        ]      = $this->fromCache($cache, $sessionId, $uids);

        $promises = [];
        if ($onLpa !== null) {
            foreach ($results as $uid => $lpa) {
                $promises[] = $onLpa($lpa, (string) $uid);
            }
        }

        if ($missing !== []) {
            $promises[] = $lookup(
                $missing,
                function (LpaInterface $lpa) use ($cache, $sessionId, $onLpa, &$results): mixed {
                    $data          = $lpa->getData() ?? throw new UnexpectedValueException('Missing LPA data');
                    $uid           = $data->getUid();
                    $results[$uid] = $lpa;

                    $this->store($cache, $sessionId, $uid, $lpa);

                    return $onLpa !== null ? $onLpa($lpa, $uid) : null;
                },
            );
        }

        return Utils::all($promises)->then(function () use (&$results): array {
            return $results;
        });
    }

    public function invalidateSession(string $sessionId): void
    {
        if (!$this->cacheFactory->taggable('lpa-in-session')->invalidateTags([$this->sessionTag($sessionId)])) {
            throw new RuntimeException('Unable to invalidate session LPA cache');
        }
    }

    public function invalidateLpa(string $uid): void
    {
        if (!$this->cacheFactory->taggable('lpa-in-session')->invalidateTags([$this->lpaTag($uid)])) {
            throw new RuntimeException('Unable to invalidate LPA cache');
        }
    }

    public function invalidateLpaInSession(string $sessionId, string $uid): void
    {
        if (!$this->cacheFactory->taggable('lpa-in-session')->deleteItem($this->cacheKey($sessionId, $uid))) {
            throw new RuntimeException('Unable to invalidate LPA in session cache');
        }
    }

    /**
     * @param string[] $uids
     * @return array{array<Lpa<LpaData>|Lpa<SiriusLpa>>, list<string>} The cached LPAs and the uids not in the cache
     */
    private function fromCache(TagAwareAdapterInterface $cache, string $sessionId, array $uids): array
    {
        $results = [];
        $missing = [];

        foreach (array_unique($uids) as $uid) {
            $cached = $this->restore($cache->getItem($this->cacheKey($sessionId, $uid))->get());

            if ($cached !== null) {
                $results[$uid] = $cached;
            } else {
                $missing[] = $uid;
            }
        }

        return [
            $results,
            $missing,
        ];
    }

    /**
     * @return Lpa<LpaData>|Lpa<SiriusLpa>|null
     */
    private function restore(mixed $cached): ?LpaInterface
    {
        if (!$cached instanceof LpaInterface) {
            return null;
        }

        $data = $cached->getData();

        if (is_array($data)) {
            return new Lpa(new SiriusLpa($data, $this->logger), $cached->getLookupTime());
        }

        if (!$data instanceof LpaData) {
            throw new UnexpectedValueException('Invalid cached LPA data');
        }

        return new Lpa($data, $cached->getLookupTime());
    }

    /**
     * @param LpaInterface<LpaData>|LpaInterface<SiriusLpa> $lpa
     */
    private function store(TagAwareAdapterInterface $cache, string $sessionId, string $uid, LpaInterface $lpa): void
    {
        $data = $lpa->getData();

        // Legacy Sirius objects contain loggers, so persist only their data and lookup time.
        $snapshot = $data instanceof SiriusLpa
            ? new Lpa(
                json_decode(json_encode($data, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR),
                $lpa->getLookupTime(),
            )
            : $lpa;

        $item = $cache->getItem($this->cacheKey($sessionId, $uid));

        $item->set($snapshot)->tag([$this->sessionTag($sessionId), $this->lpaTag($uid)]);

        if (!$cache->save($item)) {
            $this->logger->warning('Unable to cache LPA {lpaUid} in session', ['lpaUid' => $uid]);
        }
    }

    private function cacheKey(string $sessionId, string $uid): string
    {
        return 'tagged-lpa-' . hash('sha256', $sessionId . "\0" . $uid);
    }

    private function sessionTag(string $sessionId): string
    {
        return 'session-' . hash('sha256', $sessionId);
    }

    private function lpaTag(string $uid): string
    {
        return 'lpa-' . hash('sha256', $uid);
    }
}
