<?php

declare(strict_types=1);

namespace App\Service\Cache\Symfony;

use App\Service\Cache\RedisFactory;
use DI\Factory\RequestedEntry;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Redis;
use RedisException;
use RuntimeException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Exception\CacheException;
use Throwable;
use UnexpectedValueException;

final class PoolFactory implements FactoryInterface
{
    /**
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

        try {
            $config = $this->getPoolConfiguration($container, $requestedName);
            $logger = $this->getLogger($container);

            $pool = match ($config['adapter']) {
                'apcu'  => $this->createApcuPool(
                    $config['namespace'],
                    $config['default_lifetime'],
                    $config['version'],
                ),
                'array' => $this->createArrayPool($config['default_lifetime'], $config['options']),
                'redis' => $this->createRedisPool(
                    $container,
                    $config['namespace'],
                    $config['default_lifetime'],
                    $config['options'],
                ),
                default => throw new UnexpectedValueException(
                    'Unsupported Symfony cache adapter: ' . $config['adapter']
                ),
            };

            if ($logger !== null) {
                $pool->setLogger($logger);
            }

            return $pool;
        } catch (Throwable $e) {
            throw new CacheException('Unable to build a cache pool for ' . $requestedName, 0, $e);
        }
    }

    /**
     * @return array{namespace: string, version: string, adapter: string, default_lifetime: int, options: array}
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
        $options  = $pool['options'] ?? [];

        if (
            !$this->isNonEmptyString($seed)
            || !$this->isNonEmptyString($version)
            || !is_string($adapter)
            || !is_int($lifetime) || $lifetime < 0
            || !is_array($options)
        ) {
            throw new UnexpectedValueException('Invalid Symfony cache configuration for ' . $requestedName);
        }

        // Keep pool names isolated even when adapters share the same storage.
        return [
            'namespace'        => substr(hash('sha256', $seed . ':' . $requestedName), 0, 20),
            'version'          => $version,
            'adapter'          => $adapter,
            'default_lifetime' => $lifetime,
            'options'          => $options,
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
     * @throws UnexpectedValueException
     */
    private function createArrayPool(int $lifetime, array $options): ArrayAdapter
    {
        if (isset($options['serialise']) && !is_bool($options['serialise'])) {
            throw new UnexpectedValueException('Invalid array cache serialisation option');
        }

        return new ArrayAdapter(
            $lifetime,
            $options['serialise'] ?? false,
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws UnexpectedValueException
     * @throws RedisException
     */
    private function createRedisPool(
        ContainerInterface $container,
        string $namespace,
        int $lifetime,
        array $options,
    ): RedisAdapter {
        $redis = $options['connection'] ?? null;
        if ($redis === null) {
            throw new UnexpectedValueException('Missing Redis cache configuration');
        }

        if (
            !is_array($redis)
            || !$this->isNonEmptyString($redis['host'] ?? null)
            || !is_int($redis['port'] ?? null)
            || !is_int($redis['timeout'] ?? null)
            || !is_string($redis['persistent_id'] ?? null)
            || !is_int($redis['serializer'] ?? Redis::SERIALIZER_PHP)
        ) {
            throw new UnexpectedValueException('Invalid Redis cache configuration');
        }

        $factory    = $container->get(RedisFactory::class);
        $connection = $factory($container, Redis::class);
        if (!$connection instanceof Redis) {
            throw new UnexpectedValueException('Redis cache factory must return a Redis connection');
        }

        if (
            !$connection->connect(
                $redis['host'],
                $redis['port'],
                $redis['timeout'],
                $redis['persistent_id'],
            )
        ) {
            throw new RuntimeException('Unable to connect to Redis cache');
        }

        if (
            !$connection->setOption(
                Redis::OPT_SERIALIZER,
                $redis['serializer'] ?? Redis::SERIALIZER_PHP,
            )
        ) {
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
