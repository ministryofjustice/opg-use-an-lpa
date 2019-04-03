<?php

declare(strict_types=1);

namespace App\Service\Session\KeyManager;

class Config
{
    private $config;

    public function __construct(array $config)
    {
        if (empty($config['session']['key']['name'])) {
            throw new \RuntimeException('Secret name is missing');
        }

        $this->config = $config;
    }

    public function getName() : string
    {
        return $this->config['session']['key']['name'];
    }
}
