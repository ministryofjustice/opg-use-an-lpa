<?php

declare(strict_types=1);

namespace App\Enum;

enum VerificationCodeExpiryReason: string
{
    case PAPER_TO_DIGITAL ='paper_to_digital';
    case FIRST_TIME_USE = 'first_time_use';
    case CANCELLED = 'cancelled';
}
