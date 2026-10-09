<?php

declare(strict_types=1);

namespace App\Service\Cache\Symfony;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use UnexpectedValueException;

final class PoolFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        mixed $requestedName,
        ?array $options = null,
    ): AdapterInterface {
        if (!is_string($requestedName)) {
            throw new UnexpectedValueException('Symfony cache pool service name must be a string');
        }

        $config = $this->getPoolConfiguration($container, $requestedName);
        $logger = $this->getLogger($container);

        $pool = match ($config['adapter']) {
            'apcu' => $this->createApcuPool($config['namespace'], $config['default_lifetime'], $config['version']),
            'array' => new ArrayAdapter($config['default_lifetime'], true),
            default => throw new UnexpectedValueException('Unsupported Symfony cache adapter: ' . $config['adapter']),
        };

        if ($logger !== null) {
            $pool->setLogger($logger);
        }

        return $pool;
    }

    /**
     * @return array{namespace: string, version: string, adapter: string, default_lifetime: int}
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
        ];
    }

    private function createApcuPool(string $namespace, int $lifetime, string $version): ApcuAdapter
    {
        if (!ApcuAdapter::isSupported() || !apcu_enabled()) {
            throw new UnexpectedValueException('APCu cache adapter requires APCu to be enabled for this runtime');
        }

        return new ApcuAdapter($namespace, $lifetime, $version);
    }

    /**
     * @psalm-assert-if-true non-empty-string $value
     */
    private function isNonEmptyString(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }

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
