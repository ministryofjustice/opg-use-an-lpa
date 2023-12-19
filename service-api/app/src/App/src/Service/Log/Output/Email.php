<?php

declare(strict_types=1);

namespace App\Service\Log\Output;

use function hash;

class Email
{
    public function __construct(private string $email)
    {
    }

    public function __toString(): string
    {
        return hash('sha256', $this->email);
    }
}
