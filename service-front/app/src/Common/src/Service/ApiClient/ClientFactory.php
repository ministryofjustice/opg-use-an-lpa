<?php

declare(strict_types=1);

namespace Common\Service\ApiClient;

use DI\Factory\RequestedEntry;
use Http\Client\HttpClient;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Class ClientFactory
 * @package Common\Service\ApiClient
 */
class ClientFactory
{
    public function __invoke(ContainerInterface $container, RequestedEntry $entityClass)
    {
        $config = $container->get('config');

        if (!array_key_exists('api', $config)) {
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
