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
     * and by which organisation
     *
     * @param string $activityCode
     * @param string $organisation
     */
    public function recordSuccessfulLookupActivity(string $activityCode, string $organisation): void;

    /**
     * Checks activity status of all viewer codes for a specific lpa
     *
     * @param array $viewerCodes
     * @return array
     */
    public function getStatusesForViewerCodes(array $viewerCodes): array;
}
