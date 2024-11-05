<?php

declare(strict_types=1);

namespace App\Service\Lpa\ResolveActor;

use EventSauce\ObjectHydrator\MapperSettings;

#[MapperSettings(serializePublicMethods: false)]
interface HasActorInterface
{
    public function hasActor(string $uid): ?LpaActor;
}
