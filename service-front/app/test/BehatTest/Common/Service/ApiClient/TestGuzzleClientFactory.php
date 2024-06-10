<?php

declare(strict_types=1);

namespace BehatTest\Common\Service\ApiClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

/**
 * Builds a Guzzle Client instance configured with appropriate settings
 */
class TestGuzzleClientFactory
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

        return new GuzzleClient(
            [
                'base_url'    => $config['api']['uri'],
                'http_errors' => false,
                'timeout'     => 2,
                'handler'     => $container->get(HandlerStack::class),
            ]
        );
    }
}
