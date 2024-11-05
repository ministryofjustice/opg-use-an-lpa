<?php

declare(strict_types=1);

namespace App\Service\Lpa\FindActorInLpa;

use App\Service\Lpa\GetAttorneyStatus\GetAttorneyStatusInterface;
use EventSauce\ObjectHydrator\MapperSettings;

/**
 * Functionality required to allow the working of the FindActorInLpa invokable service
 */
#[MapperSettings(serializePublicMethods: false)]
interface FindActorInLpaInterface
{
    /** @return array<GetAttorneyStatusInterface & ActorMatchingInterface> */
    public function getAttorneys(): array;

    public function getDonor(): GetAttorneyStatusInterface & ActorMatchingInterface;

    public function getUid(): string;
}
