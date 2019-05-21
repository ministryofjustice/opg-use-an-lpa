<?php

declare(strict_types=1);

namespace Viewer\Service\ApiClient;

use RuntimeException;

/**
 * Class Config
 * @package Viewer\Service\ApiClient
 */
class Config
{
    /**
     * @var array
     */
    private $config;

    /**
     * Config constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (!array_key_exists('session', $config)) {
            throw new RuntimeException('API configuration missing');
        }

        if (!array_key_exists('uri', $config['api'])) {
            throw new RuntimeException('Missing API configuration: uri');
        }

        $this->config = $config;
    }

    public function getApiUri() : string
    {
        return $this->config['api']['uri'];
    }

    public function getToken() : ?string
    {
        return null;
    }
}
