<?php

declare(strict_types=1);

namespace Viewer\Service\ApiClient;

use Http\Client\HttpClient;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Class ClientFactory
 * @package Viewer\Service\ApiClient
 */
class ClientFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['api'])) {
            throw new RuntimeException('API configuration missing');
        }

        if (!array_key_exists('uri', $config['api'])) {
            throw new RuntimeException('Missing API configuration: uri');
        }

        return new Client(
            $container->get(HttpClient::class),
            $config['api']['uri'],
            null
        );
    }
}
