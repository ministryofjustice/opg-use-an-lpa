<?php

declare(strict_types=1);

namespace Viewer\Service\Session\KeyManager;

use RuntimeException;

class Config
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        if (empty($config['session']['key']['alias'])) {
            throw new RuntimeException('KMS CMK alias is missing');
        }

        $this->config = $config;
    }

    public function getKeyAlias() : string
    {
        return $this->config['session']['key']['alias'];
    }
}
