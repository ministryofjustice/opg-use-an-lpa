<?php

declare(strict_types=1);

namespace Common\Entity;

class Code
{
    public function __construct(
        public readonly string $value,
    ) {
    }

    public function isPaperVerificationCode(): bool
    {
        return strlen($this->value) === 19 && $this->value[0] === 'P';
    }
}
