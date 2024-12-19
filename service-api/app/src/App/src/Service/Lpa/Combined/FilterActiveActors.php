<?php

declare(strict_types=1);

namespace App\Service\Lpa\Combined;

use App\Service\Lpa\GetAttorneyStatus;
use App\Service\Lpa\GetAttorneyStatus\AttorneyStatus;
use App\Service\Lpa\GetTrustCorporationStatus;
use App\Service\Lpa\GetTrustCorporationStatus\TrustCorporationStatus;

class FilterActiveActors
{
    public function __construct(
        private readonly GetAttorneyStatus $getAttorneyStatus,
        private readonly GetTrustCorporationStatus $getTrustCorporationStatus,
    ) {
    }

    /**
     * @template T
     * @param FilterActiveActorsInterface $lpa
     * @psalm-param T $lpa
     * @return FilterActiveActorsInterface
     * @psalm-return T
     */
    public function __invoke(FilterActiveActorsInterface $lpa): FilterActiveActorsInterface
    {
        $attorneys =
            array_filter($lpa->getAttorneys(), function ($attorney) {
                return ($this->getAttorneyStatus)($attorney) === AttorneyStatus::ACTIVE_ATTORNEY;
            });

        $trustCorporations =
            array_filter($lpa->getTrustCorporations(), function ($trustCorporation) {
                return ($this->getTrustCorporationStatus)($trustCorporation)
                    === TrustCorporationStatus::ACTIVE_TC;
            });

        return $lpa
            ->withAttorneys($attorneys)
            ->withTrustCorporations($trustCorporations);
    }
}
