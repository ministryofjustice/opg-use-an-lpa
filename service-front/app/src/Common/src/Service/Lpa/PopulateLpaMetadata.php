<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;
use Psr\Log\LoggerInterface;

/**
 * Given a collection of LPAs will attach metadata to each LPA describing the number of active ViewerCodes
 * and the LPAs active status
 */
class PopulateLpaMetadata
{
    public function __construct(private ViewerCodeService $viewerCodeService, private LoggerInterface $logger)
    {
    }

    /**
     * Queries the ViewerCodeService for information about the LPAs and attaches it as
     * metadata to the LPA record withing the ArrayObject.
     *
     * @param ArrayObject $lpas      A list of LPAs to attach metadata to
     * @param string      $userToken An identity used to query the viewer code service
     * @return ArrayObject
     */
    public function __invoke(ArrayObject $lpas, string $userToken): ArrayObject
    {
        foreach ($lpas as $lpaKey => $lpaData) {
            // temporary DEBUG
            $this->logger->notice('Populating LPA metadata for ' . $lpaKey, $lpaData->getArrayCopy());
            //temporary DEBUG - DO NOT LET LIVE

            $actorToken = $lpaData['user-lpa-actor-token'];

            $shareCodes = $this->viewerCodeService->getShareCodes(
                $userToken,
                $actorToken,
                true
            );

            $lpas->$lpaKey->activeCodeCount = $shareCodes->activeCodeCount;
            $lpas->$lpaKey->shareCodes      = $shareCodes;
            $lpas->$lpaKey->actorActive     =
                $lpaData['actor']['type'] === 'donor' || $lpaData['actor']['details']->getSystemStatus();
        }

        return $lpas;
    }
}
