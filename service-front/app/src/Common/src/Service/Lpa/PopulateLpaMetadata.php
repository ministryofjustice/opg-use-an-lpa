<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;
use Generator;
use GuzzleHttp\Promise\Each;

/**
 * Given a collection of LPAs will attach metadata to each LPA describing the number of active ViewerCodes
 * and the LPAs active status
 */
class PopulateLpaMetadata
{
    /**
     * The maximum number of viewer code requests that will be in flight to the API at once
     */
    public const int CONCURRENCY = 10;

    public function __construct(private ViewerCodeService $viewerCodeService)
    {
    }

    /**
     * Queries the ViewerCodeService for information about the LPAs and attaches it as
     * metadata to the LPA record withing the ArrayObject.
     *
     * The viewer code requests are made concurrently, with each LPA being populated as its response arrives.
     *
     * @param ArrayObject $lpas      A list of LPAs to attach metadata to
     * @return ArrayObject
     */
    public function __invoke(ArrayObject $lpas): ArrayObject
    {
        Each::ofLimitAll($this->populate($lpas), self::CONCURRENCY)->wait();

        return $lpas;
    }

    /**
     * Lazily generates a promise per LPA so that requests are only started as concurrency allows.
     */
    private function populate(ArrayObject $lpas): Generator
    {
        foreach ($lpas as $lpaKey => $lpaData) {
            $lpas->$lpaKey->actorActive =
                $lpaData['actor']['type'] === 'donor' || $lpaData['actor']['details']->getSystemStatus();

            yield $this->viewerCodeService
                ->getShareCodesAsync($lpaData['user-lpa-actor-token'], true)
                ->then(function (ArrayObject $shareCodes) use ($lpas, $lpaKey): void {
                    $lpas->$lpaKey->activeCodeCount = $shareCodes['activeCodeCount'];
                    $lpas->$lpaKey->shareCodes      = $shareCodes;
                });
        }
    }
}
