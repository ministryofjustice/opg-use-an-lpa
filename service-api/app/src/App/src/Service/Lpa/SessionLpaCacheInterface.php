<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\Repository\Response\LpaInterface;
use App\Entity\Lpa as LpaData;

interface SessionLpaCacheInterface
{
    /**
     * @param string[] $uids
     * @param callable(list<string>): array<LpaInterface<LpaData>|LpaInterface<SiriusLpa>> $lookup
     * @return array<LpaInterface<LpaData>|LpaInterface<SiriusLpa>>
     */
    public function lookup(array $uids, ?string $sessionId, callable $lookup): array;

    public function invalidateSession(string $sessionId): void;

    public function invalidateLpa(string $uid): void;

    public function invalidateLpaInSession(string $sessionId, string $uid): void;
}
