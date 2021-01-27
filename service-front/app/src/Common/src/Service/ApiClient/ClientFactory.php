<?php

declare(strict_types=1);

namespace Common\Service\ApiClient;

use Common\Service\Log\RequestTracing;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

/**
 * Class ClientFactory
 * @package Common\Service\ApiClient
 */
class ClientFactory
{
    public function __invoke(ContainerInterface $container): Client
    {
        $config = $container->get('config');

        if (!array_key_exists('api', $config)) {
            throw new RuntimeException('API configuration missing');
        }

        if (!array_key_exists('uri', $config['api'])) {
            throw new RuntimeException('Missing API configuration: uri');
        }

        return new Client(
            $container->get(ClientInterface::class),
            $config['api']['uri'],
            $container->get(RequestTracing::TRACE_PARAMETER_NAME)
        );
    }
}
