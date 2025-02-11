<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\Exception\NotFoundException;
use DateTime;

/**
 * Interface for Data relating to Viewer Codes
 *
 * @psalm-type ViewerCode = array{
 *     ViewerCode: string,
 *     Added: string,
 *     Expires: string,
 *     Organisation: string,
 *     SiriusUid?: string,
 *     LpaUid?: string,
 *     UserLpaActor: string,
 *     CreatedBy?: string,
 *     Cancelled?: string,
 * }
 */
interface ViewerCodesInterface
{
    /**
     * Get a viewer code from the database
     *
     * @param string $code
     * @psalm-return ViewerCode|null
     * @return array|null
     */
    public function get(string $code): ?array;

    /**
     * Gets a list of viewer codes for a given LPA
     *
     * @param string $siriusUid
     * @psalm-return ViewerCode[]
     * @return array
     */
    public function getCodesByLpaId(string $siriusUid): array;

    /**
     * Adds a code to the database.
     *
     * $siriusUid is denormalised.
     *
     * @param string        $code
     * @param string        $userLpaActorToken
     * @param string|null   $siriusUid
     * @param string|null   $lpaUid
     * @param DateTime      $expires
     * @param string        $organisation
     * @param string|null   $actorId
     *
     * @return void
     */
    public function add(
        string $code,
        string $userLpaActorToken,
        ?string $siriusUid,
        ?string $lpaUid,
        DateTime $expires,
        string $organisation,
        ?string $actorId,
    ): void;

    /**
     * Cancels a code in the database.
     *
     * @param string   $code
     * @param DateTime $cancelledDate
     * @return bool The code cancellation was successful or not
     */
    public function cancel(string $code, DateTime $cancelledDate): bool;

    /**
     * update a viewer code from the database
     *
     * @param string $code
     * @param int    $codeOwner
     * @return bool
     */
    public function removeActorAssociation(string $code, int $codeOwner): bool;
}
