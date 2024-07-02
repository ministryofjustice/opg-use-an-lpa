<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\DataAccess\Repository\Response\LpaInterface;
use Psr\Http\Message\ResponseInterface;

interface RequestLetterInterface
{
    /**
     * requests letter for an LPA based on its Sirius uid.
     */

    /**
     * @param int $caseId The Sirius uId of an LPA
     * @param int $actorId The uId of an actor as found attached to an LPA
     */
    public function requestLetter(int $caseId, ?int $actorId, ?string $additionalInfo): void;
}
