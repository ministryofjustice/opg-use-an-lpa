<?php

declare(strict_types=1);

namespace Common\Service\Security;

use function Sodium\randombytes_buf;
use function Sodium\bin2hex;

final class CSPNonce
{
    public function __construct(private ?string $value = null)
    {
    }

    public function __toString(): string
    {
        if ($this->value === null) {
            $this->value = bin2hex(randombytes_buf(16));
        }

        return $this->value;
    }
}
