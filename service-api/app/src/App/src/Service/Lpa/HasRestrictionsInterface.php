<?php

declare(strict_types=1);

namespace App\Service\Lpa;

interface HasRestrictionsInterface
{
    public function hasGuidance(): bool;

    public function hasRestrictions(): bool;
}
