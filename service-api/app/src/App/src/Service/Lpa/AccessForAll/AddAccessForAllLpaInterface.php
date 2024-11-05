<?php

declare(strict_types=1);

namespace App\Service\Lpa\AccessForAll;

use App\Service\Lpa\SiriusPerson;
use EventSauce\ObjectHydrator\MapperSettings;

#[MapperSettings(serializePublicMethods: false)]
interface AddAccessForAllLpaInterface
{
    public function getDonor(): SiriusPerson;

    public function getUid(): string;
}
