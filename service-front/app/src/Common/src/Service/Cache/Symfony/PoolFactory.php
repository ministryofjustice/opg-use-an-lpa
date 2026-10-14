<?php

declare(strict_types=1);

namespace Common\Service\Cache\Symfony;

use Common\Service\Cache\RedisFactory;
use DI\Factory\RequestedEntry;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Redis;
use RuntimeException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Exception\CacheException;
use UnexpectedValueException;

final class PoolFactory implements FactoryInterface
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws UnexpectedValueException
     * @throws ContainerExceptionInterface
     * @throws RuntimeException
     * @throws CacheException
     */
    public function __invoke(
        ContainerInterface $container,
        mixed $requestedName,
        ?array $options = null,
    ): AdapterInterface {
        if ($requestedName instanceof RequestedEntry) {
            $requestedName = $requestedName->getName();
        }

        $config = $this->getPoolConfiguration($container, $requestedName);
        $logger = $this->getLogger($container);

        $pool = match ($config['adapter']) {
            'apcu' => $this->createApcuPool($config['namespace'], $config['default_lifetime'], $config['version']),
            'array' => new ArrayAdapter($config['default_lifetime'], true),
            'redis' => $this->createRedisPool(
                $container,
                $config['namespace'],
                $config['default_lifetime'],
                $config['redis'],
            ),
            default => throw new UnexpectedValueException('Unsupported Symfony cache adapter: ' . $config['adapter']),
        };

        if ($logger !== null) {
            $pool->setLogger($logger);
        }

        return $pool;
    }

    /**
     * @return array{
     *     namespace: string,
     *     version: string,
     *     adapter: string,
     *     default_lifetime: int,
     *     redis: array{host: string, port: int, timeout: int, persistent_id: string, serializer: int}|null,
     * }
     * @throws UnexpectedValueException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getPoolConfiguration(ContainerInterface $container, string $requestedName): array
    {
        $config = $container->get('config');

        if (!is_array($config) || !is_array($config['symfony_cache'] ?? null)) {
            throw new UnexpectedValueException('Missing Symfony cache configuration for ' . $requestedName);
        }

        $cache = $config['symfony_cache'];
        $pools = $cache['pools'] ?? null;

        if (!is_array($pools) || !is_array($pools[$requestedName] ?? null)) {
            throw new UnexpectedValueException('Missing Symfony cache configuration for ' . $requestedName);
        }

        $pool = $pools[$requestedName];

        $seed     = $cache['namespace'] ?? null;
        $version  = $cache['version'] ?? null;
        $adapter  = $pool['adapter'] ?? null;
        $lifetime = $pool['default_lifetime'] ?? null;

        if (
            !$this->isNonEmptyString($seed)
            || !$this->isNonEmptyString($version)
            || !is_string($adapter)
            || !is_int($lifetime) || $lifetime < 0
        ) {
            throw new UnexpectedValueException('Invalid Symfony cache configuration for ' . $requestedName);
        }

        // Keep pool names isolated even when adapters share the same storage.
        return [
            'namespace'        => substr(hash('sha256', $seed . ':' . $requestedName), 0, 20),
            'version'          => $version,
            'adapter'          => $adapter,
            'default_lifetime' => $lifetime,
            'redis'            => $pool['redis'] ?? $cache['redis'] ?? null,
        ];
    }

    /**
     * @throws CacheException
     */
    private function createApcuPool(string $namespace, int $lifetime, string $version): ApcuAdapter
    {
        return new ApcuAdapter($namespace, $lifetime, $version);
    }

    /**
     * @param array{host: string, port: int, timeout: int, persistent_id: string, serializer: int}|null $redis
     * @return RedisAdapter
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws UnexpectedValueException
     * @throws \RedisException
     */
    private function createRedisPool(
        ContainerInterface $container,
        string $namespace,
        int $lifetime,
        ?array $redis,
    ): RedisAdapter {
        if ($redis === null) {
            throw new UnexpectedValueException('Missing Redis cache configuration');
        }

        $factory = $container->get(RedisFactory::class);

        if (!$factory instanceof RedisFactory) {
            throw new UnexpectedValueException('Redis cache factory must be a RedisFactory instance');
        }

        $connection = $factory();

        if (!$connection->connect($redis['host'], $redis['port'], $redis['timeout'], $redis['persistent_id'])) {
            throw new RuntimeException('Unable to connect to Redis cache');
        }

        if (!$connection->setOption(Redis::OPT_SERIALIZER, $redis['serializer'])) {
            throw new RuntimeException('Unable to configure Redis cache serializer');
        }

        return new RedisAdapter($connection, $namespace, $lifetime);
    }

    /**
     * @psalm-assert-if-true non-empty-string $value
     */
    private function isNonEmptyString(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws UnexpectedValueException
     */
    private function getLogger(ContainerInterface $container): ?LoggerInterface
    {
        if (!$container->has(LoggerInterface::class)) {
            return null;
        }

        $logger = $container->get(LoggerInterface::class);

        if (!$logger instanceof LoggerInterface) {
            throw new UnexpectedValueException('Symfony cache logger must implement LoggerInterface');
        }

        return $logger;
    }
}
