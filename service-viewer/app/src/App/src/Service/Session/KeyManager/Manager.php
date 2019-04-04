<?php

declare(strict_types=1);

namespace App\Service\Session\KeyManager;

class Manager
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }
}
