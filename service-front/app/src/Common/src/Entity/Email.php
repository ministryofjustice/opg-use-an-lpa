<?php

declare(strict_types=1);

namespace Common\Service\Log;

class EmailHash
{
    private $email;

    public function __construct($email)
    {
        $this->email = $email;
    }

    public function hash($email)
    {
        $this->email = hash('sha256', $email);

        return $this->email;
    }
}
