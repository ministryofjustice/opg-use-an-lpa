<?php

declare(strict_types=1);

namespace App\Service\Lpa\IsValid;

trait StatusValidationTrait
{
    public function isRegisteredOrCancelled(string $status): bool
    {
        $status = strtolower($status);

        return in_array($status, [LpaStatus::REGISTERED->value, LpaStatus::CANCELLED->value]);
    }
}
