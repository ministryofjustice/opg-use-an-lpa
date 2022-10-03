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
        // TODO: UML-674 Assess whether a salt is required to make this more secure
        // If the salt is random, then it will add difficulty to search for a particular user
        $hash = hash('sha256', $this->email);
        return sprintf($hash);
    }
}
