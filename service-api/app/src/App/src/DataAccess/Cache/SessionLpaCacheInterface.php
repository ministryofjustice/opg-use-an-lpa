<?php

declare(strict_types=1);

namespace App\DataAccess\Cache;

use App\DataAccess\Repository\Response\LpaInterface;
use App\Entity\Lpa as LpaData;
use App\Service\Lpa\SiriusLpa;
use GuzzleHttp\Promise\PromiseInterface;

interface SessionLpaCacheInterface
{
    /**
     * @param string[] $uids
     * @param callable(list<string>): array<LpaInterface<LpaData>|LpaInterface<SiriusLpa>> $lookup
     * @return array<LpaInterface<LpaData>|LpaInterface<SiriusLpa>>
     */
    public function lookup(array $uids, ?string $sessionId, callable $lookup): array;

    /**
     * Asynchronous version of {@see SessionLpaCacheInterface::lookup()}.
     *
     * The callback is invoked for each LPA, whether it was found in the cache or fetched using the lookup, and
     * LPAs fetched using the lookup are stored as each one becomes available.
     *
     * @param string[] $uids
     * @param callable(list<string>, (callable(LpaInterface, string): mixed)|null): PromiseInterface $lookup
     * @param (callable(LpaInterface, string): (PromiseInterface|null))|null $onLpa
     * @return PromiseInterface Fulfils with array<LpaInterface<LpaData>|LpaInterface<SiriusLpa>>
     */
    public function lookupAsync(
        array $uids,
        ?string $sessionId,
        callable $lookup,
        ?callable $onLpa = null,
    ): PromiseInterface;

    /** @psalm-api */
    public function invalidateSession(string $sessionId): void;

    /** @psalm-api */
    public function invalidateLpa(string $uid): void;

    /** @psalm-api */
    public function invalidateLpaInSession(string $sessionId, string $uid): void;
}
