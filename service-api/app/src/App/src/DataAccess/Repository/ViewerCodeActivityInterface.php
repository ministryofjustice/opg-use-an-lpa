<?php

declare(strict_types=1);

namespace App\DataAccess\Repository;

/**
 * Interface for recording activity around the Viewer Code.
 *
 * @psalm-import-type ViewerCode from ViewerCodesInterface
 * @psalm-type ViewerCodeActivity = array{
 *     ViewerCode: string,
 *     ViewedBy: string,
 *     Viewed: string,
 * }
 * @psalm-type ViewerCodeWithActivity = array{
 *     ViewerCode: string,
 *     Added: string,
 *     Expires: string,
 *     Organisation: string,
 *     SiriusUid: string,
 *     UserLpaActor: string,
 *     CreatedBy?: int,
 *     Cancelled?: string,
 *     Viewed: ViewerCodeActivity|false,
 * }
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
     * @psalm-param ViewerCode[] $viewerCodes
     * @psalm-return ViewerCodeWithActivity[]
     * @return array
     */
    public function getStatusesForViewerCodes(array $viewerCodes): array;
}
