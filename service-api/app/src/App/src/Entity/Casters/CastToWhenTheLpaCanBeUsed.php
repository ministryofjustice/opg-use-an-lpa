<?php

declare(strict_types=1);

namespace App\Entity\Casters;

use App\Enum\WhenTheLpaCanBeUsed;
use Attribute;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastToWhenTheLpaCanBeUsed implements PropertyCaster
{
    public function cast(mixed $value, ObjectMapper $hydrator): ?string
    {
        if (is_null(WhenTheLpaCanBeUsed::tryFrom($value))) {
            $value = match ($value) {
                'when registered' => WhenTheLpaCanBeUsed::WHEN_HAS_CAPACITY->value,
                'loss of capacity' => WhenTheLpaCanBeUsed::WHEN_CAPACITY_LOST->value,
                default => '',
            };
        }

        return WhenTheLpaCanBeUsed::from($value)->value;
    }
}
