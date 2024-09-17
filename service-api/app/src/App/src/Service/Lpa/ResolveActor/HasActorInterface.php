<?php

declare(strict_types=1);

namespace App\Service\Lpa\ResolveActor;

interface HasActorInterface
{
    public function hasActor(string $uid): ?LpaActor;
}
