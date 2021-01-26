<?php

declare(strict_types=1);

namespace Common\Service\ApiClient;

use Http\Adapter\Guzzle6\Client as GuzzleClient;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

/**
 * Class ClientFactory
 *
 * Builds a Guzzle Client instance configured with appropriate settings
 *
 * @package Common\Service\ApiClient\Guzzle
 */
class GuzzleClientFactory
{
    public function __invoke(ContainerInterface $container): ClientInterface
    {
        $config = $container->get('config');

        if (!array_key_exists('api', $config)) {
            throw new RuntimeException('API configuration missing');
        }

        if (!array_key_exists('uri', $config['api'])) {
            throw new RuntimeException('Missing API configuration: uri');
        }

        return GuzzleClient::createWithConfig(
            [
                'base_url' => $config['api']['uri'],
                'http_errors' => false
            ]
        );
    }
}
