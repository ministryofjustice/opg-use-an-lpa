<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\Entity\Value\LpaUid;
use DateTime;
use DateTimeInterface;

/**
 * Interface for Data relating to Viewer Codes
 *
 * @psalm-type ViewerCode = array{
 *     ViewerCode: string,
 *     Added: DateTimeInterface,
 *     Expires: DateTimeInterface,
 *     Organisation: string,
 *     SiriusUid?: string,
 *     LpaUid?: string,
 *     UserLpaActor: string,
 *     CreatedBy?: string,
 *     Cancelled?: DateTimeInterface,
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
     * @param LpaUid $lpaUid
     * @psalm-return ViewerCode[]
     * @return array
     */
    public function getCodesByLpaId(LpaUid $lpaUid): array;

    /**
     * Adds a code to the database.
     *
     * @param string        $code
     * @param string        $userLpaActorToken
     * @param LpaUid        $lpaUid
     * @param DateTime      $expires
     * @param string        $organisation
     * @param string|null   $actorId
     * @return void
     */
    public function add(
        string $code,
        string $userLpaActorToken,
        LpaUid $lpaUid,
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
    public function removeActorAssociation(string $code, string $codeOwner): bool;
}
