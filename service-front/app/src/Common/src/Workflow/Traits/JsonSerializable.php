<?php

declare(strict_types=1);

namespace Common\Workflow\Traits;

use DateTimeImmutable;

trait JsonSerializable
{
    public function jsonSerialize(): array
    {
        $serialized = [];

        foreach (get_object_vars($this) as $prop => $value) {
            if ($value !== null) {
                if ($value instanceof DateTimeImmutable) {
                    $serialized[$prop] = $value->format('c');
                } else {
                    $serialized[$prop] = $value;
                }
            }
        }

        return $serialized;
    }
}
