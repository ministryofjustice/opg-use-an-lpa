<?php

declare(strict_types=1);

namespace Common\Service\Security;

use Common\Service\Security\RateLimit\KeyedRateLimitService;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class RateLimitServiceFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns a configured rate limit service loaded using the name given
     *
     * @param string $limitName
     *
     * @return RateLimitService
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function factory(string $limitName): RateLimiterInterface
    {
        $config = $this->container->get('config');

        if (!isset($config['ratelimits'])) {
            throw new RuntimeException('Missing rate limits configuration');
        }

        if (!isset($config['ratelimits'][$limitName])) {
            throw new RuntimeException('Missing rate limits configuration for ' . $limitName);
        }

        if (!isset($config['ratelimits'][$limitName]['type'])) {
            throw new RuntimeException('Missing rate limit type configuration for ' . $limitName);
        }

        $factory = $this->container->get(StorageAdapterFactoryInterface::class);

        $config['ratelimits'][$limitName]['storage']['options']['namespace'] = $limitName;
        $cacheAdaptor = $factory->createFromArrayConfiguration($config['ratelimits'][$limitName]['storage']);

        $type = $config['ratelimits'][$limitName]['type'];
        switch ($type) {
            case 'keyed':
                return new KeyedRateLimitService(
                    $cacheAdaptor,
                    $config['ratelimits'][$limitName]['options']['interval'] ?? 300,
                    $config['ratelimits'][$limitName]['options']['requests_per_interval'] ?? 60,
                    $this->container->get(LoggerInterface::class)
                );

                break;
            default:
                throw new RuntimeException('No class available for rate limit type ' . $type);
        }
    }

    /**
     * Returns all configured rate limit services
     *
     * @return RateLimitService[]
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function all(): array
    {
        $config = $this->container->get('config');

        if (!isset($config['ratelimits'])) {
            throw new RuntimeException('Missing rate limits configuration');
        }

        return array_map(function (string $limitName) {
            return $this->factory($limitName);
        }, array_keys($config['ratelimits']));
    }
}
