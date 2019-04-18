<?php

declare(strict_types=1);

namespace Viewer\Service\Lpa;

use Viewer\Service\ApiClient\Client as ApiClient;
use Psr\Container\ContainerInterface;

/**
 * Class LpaServiceFactory
 * @package Viewer\Service\Log
 */
class LpaServiceFactory
{
    /**
     * @param ContainerInterface $container
     * @param $name
     * @param callable $callback
     * @param array|null $options
     * @return LpaService
     */
    public function __invoke(ContainerInterface $container)
    {
        $apiClient = $container->get(ApiClient::class);

        return new LpaService($apiClient);
    }
}
