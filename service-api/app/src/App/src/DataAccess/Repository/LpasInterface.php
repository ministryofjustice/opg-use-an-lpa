<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\DataAccess\Repository\Response\LpaInterface;

interface LpasInterface
{
    /**
     * Looks up an LPA based on its Sirius uid.
     */
    public function get(string $uid): ?LpaInterface;

    /**
     * Looks up the all the LPA uids in the passed array.
     *
     * @param string[] $uids
     * @return LpaInterface[]
     */
    public function lookup(array $uids): array;    // array of Lpa objects.

    /**
     * @param int $caseId The Sirius uId of an LPA
     * @param int $actorId The uId of an actor as found attached to an LPA
     */
    public function requestLetter(int $caseId, int $actorId): void;
}
