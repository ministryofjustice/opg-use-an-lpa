<?php

declare(strict_types=1);

namespace App\Value;

class PaperVerificationCode
{
    public function __construct(
        private string $code,
    ) {
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
