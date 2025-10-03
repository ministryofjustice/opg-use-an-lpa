<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

use App\Enum\VerificationCodeExpiryReason;
use App\Value\LpaUid;
use DateTimeInterface;

final class PaperVerificationCode
{
    public function __construct(
        public readonly LpaUid $lpaUid,
        public readonly bool $cancelled,
        public readonly ?DateTimeInterface $expiresAt,
        public readonly ?VerificationCodeExpiryReason $expiryReason,
    ) {
    }
}
