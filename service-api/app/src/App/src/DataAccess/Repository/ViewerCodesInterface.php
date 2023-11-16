<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use App\Exception\NotFoundException;
use DateTime;

interface ViewerCodesInterface
{
    /**
     * Get a viewer code from the database
     *
     * @param string $code
     * @return array
     */
    public function get(string $code): ?array;

    /**
     * Gets a list of viewer codes for a given LPA
     *
     * @param string $siriusUid
     * @return array
     */
    public function getCodesByLpaId(string $siriusUid): array;

    /**
     * Adds a code to the database.
     *
     * $siriusUid is denormalised.
     *
     * @param string   $code
     * @param string   $userLpaActorToken
     * @param string   $siriusUid
     * @param DateTime $expires
     * @param string   $organisation
     * @return mixed
     */
    public function add(
        string $code,
        string $userLpaActorToken,
        string $siriusUid,
        DateTime $expires,
        string $organisation,
        ?int $actorId,
    );

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
     * @return bool
     */
    public function removeActorAssociation(string $code, int $codeOwner): bool;
}
