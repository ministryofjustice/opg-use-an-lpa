<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

use DateTime;

interface ViewerCodesInterface
{
    /**
     * Get a viewer code from the database
     *
     * @param string $code
     * @return array
     */
    public function get(string $code) : ?array;

    /**
     * Get a list of viewer codes from the database for a given LPA
     *
     * @param string $siriusUid
     * @param string $userLpaActorId
     * @return array|null
     */
    public function getCodesByUserLpaActorId(string $siriusUid, string $userLpaActorId) : ?array;

    /**
     * Adds a code to the database.
     *
     * $siriusUid is denormalised.
     *
     * @param string $code
     * @param string $userLpaActorToken
     * @param string $siriusUid
     * @param DateTime $expires
     * @param string $organisation
     * @return mixed
     */
    public function add(string $code, string $userLpaActorToken, string $siriusUid, DateTime $expires, string $organisation);
}
