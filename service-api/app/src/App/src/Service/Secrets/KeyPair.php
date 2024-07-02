<?php

declare(strict_types=1);

namespace App\Service\Secrets;

use ParagonIE\HiddenString\HiddenString;

class KeyPair
{
    public function __construct(readonly public string $public, readonly public ?HiddenString $private = null)
    {
    }

    public function hasPrivate(): bool
    {
        return $this->private !== null;
    }
}
