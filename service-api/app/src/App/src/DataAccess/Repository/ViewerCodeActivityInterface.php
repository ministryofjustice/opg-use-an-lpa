<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

/**
 * Interface for recording activity around the Viewer Code.
 *
 * Interface ViewerCodeActivityInterface
 * @package App\DataAccess\Repository
 */
interface ViewerCodeActivityInterface
{
    /**
     * Records the fact that a given code has just been successfully accessed
     *
     * @param string $activityCode
     */
    public function recordSuccessfulLookupActivity(string $activityCode) : void;
}
