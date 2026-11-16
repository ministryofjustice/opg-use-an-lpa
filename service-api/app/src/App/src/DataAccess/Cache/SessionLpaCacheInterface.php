<?php

declare(strict_types=1);

namespace App\DataAccess\Cache;

use App\DataAccess\Repository\Response\LpaInterface;
use App\Entity\Lpa as LpaData;
use App\Service\Lpa\SiriusLpa;

interface SessionLpaCacheInterface
{
    /**
     * @param string[] $uids
     * @param callable(list<string>): array<LpaInterface<LpaData>|LpaInterface<SiriusLpa>> $lookup
     * @return array<LpaInterface<LpaData>|LpaInterface<SiriusLpa>>
     */
    public function lookup(array $uids, ?string $sessionId, callable $lookup): array;

    /** @psalm-api */
    public function invalidateSession(string $sessionId): void;

    /** @psalm-api */
    public function invalidateLpa(string $uid): void;

    /** @psalm-api */
    public function invalidateLpaInSession(string $sessionId, string $uid): void;
}
