<?php

declare(strict_types=1);

namespace App\Service\Lpa\ResolveActor;

interface ResolveActorInterface
{
    public function getId(): string;

    public function getUid(): string;
}
