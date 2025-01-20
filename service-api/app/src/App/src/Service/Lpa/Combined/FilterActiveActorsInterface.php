<?php

declare(strict_types=1);

namespace App\Service\Lpa\Combined;

use App\Entity\Person;
use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use App\Service\Lpa\GetTrustCorporationStatus\GetTrustCorporationStatusInterface;

interface FilterActiveActorsInterface
{
    /**
     * @return GetAttorneyStatusInterface[]
     */
    public function getAttorneys(): array;

    /**
     * @param Person[] $attorneys
     * @return self
     */
    public function withAttorneys(array $attorneys): self;

    /**
     * @return GetTrustCorporationStatusInterface[]
     */
    public function getTrustCorporations(): array;

    /**
     * @param Person[] $trustCorporations
     * @return self
     */
    public function withTrustCorporations(array $trustCorporations): self;
}
