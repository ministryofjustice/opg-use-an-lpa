<?php

declare(strict_types=1);

namespace Common\Service\Security;

use function random_bytes;
use function bin2hex;

final class CSPNonce
{
    public function __construct(private ?string $value = null)
    {
    }

    public function __toString(): string
    {
        if ($this->value === null) {
            $this->value = bin2hex(random_bytes(16));
        }

        return $this->value;
    }
}
