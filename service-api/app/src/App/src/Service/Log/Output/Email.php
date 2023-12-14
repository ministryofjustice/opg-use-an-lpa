<?php

declare(strict_types=1);

namespace Common\Service\Log\Output;

use function hash;

class Email
{
    public function __construct(private string $email)
    {
    }

    public function __toString(): string
    {
        $hash = hash('sha256', $this->email);
        return sprintf($hash);
    }
}
