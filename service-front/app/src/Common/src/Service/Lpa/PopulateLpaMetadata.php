<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;

/**
 * Given a collection of LPAs will attach metadata to each LPA describing the number of active ViewerCodes
 * and the LPAs active status
 */
class PopulateLpaMetadata
{
    /**
     * @param ViewerCodeService $viewerCodeService
     * @codeCoverageIgnore
     */
    public function __construct(private ViewerCodeService $viewerCodeService)
    {
    }

    /**
     * Queries the ViewerCodeService for information about the LPAs and attaches it as
     * metadata to the LPA record withing the ArrayObject.
     *
     * @param ArrayObject $lpas      A list of LPAs to attache metadata to
     * @param string      $userToken A identity used to query the viewer code service
     * @return ArrayObject
     */
    public function __invoke(ArrayObject $lpas, string $userToken): ArrayObject
    {
        foreach ($lpas as $lpaKey => $lpaData) {
            $actorToken = $lpaData['user-lpa-actor-token'];

            $shareCodes = $this->viewerCodeService->getShareCodes(
                $userToken,
                $actorToken,
                true
            );

            $lpas->$lpaKey->activeCodeCount = $shareCodes->activeCodeCount;
            $lpas->$lpaKey->actorActive     =
                $lpaData['actor']['type'] === 'donor' || $lpaData['actor']['details']->getSystemStatus();
        }

        return $lpas;
    }
}
