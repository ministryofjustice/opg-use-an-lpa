<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Entity\Person;
use EventSauce\ObjectHydrator\MapperSettings;

#[MapperSettings(serializePublicMethods: false)]
interface SortLpasInterface
{
    public function getDonor(): Person;

    public function getCaseSubtype(): string;
}
