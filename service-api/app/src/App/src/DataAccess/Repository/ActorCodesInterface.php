<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

/**
 * Interface for recording activity around the Viewer Code.
 *
 * @psalm-type ViewerCodeWithActivity = array{
 *     SiriusUid: string,
 *     ActorLpaId: int,
 *     Expires: string,
 *     Active: bool,
 *     ActorCode: string,
 * }
 */
interface ActorCodesInterface
{
    /**
     * Get an actor LPA code and actor details from the database.
     *
     * @param string $code
     * @psalm-return ViewerCodeWithActivity|null
     * @return array|null
     */
    public function get(string $code): ?array;

    /**
     * Marks a given actor code as used.
     * It will not be able to be used again.
     *
     * @param string $code
     */
    public function flagCodeAsUsed(string $code);
}
