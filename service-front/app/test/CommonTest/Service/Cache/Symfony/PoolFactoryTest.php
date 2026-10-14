<?php

declare(strict_types=1);

namespace CommonTest\Service\Cache\Symfony;

use Common\Service\Cache\RedisFactory;
use Common\Service\Cache\Symfony\PoolFactory;
use DI\Factory\RequestedEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Redis;
use RuntimeException;
use stdClass;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use UnexpectedValueException;

#[CoversClass(PoolFactory::class)]
final class PoolFactoryTest extends TestCase
{
    #[Test]
    public function it_should_create_an_array_pool_for_a_phpdi_entry_when_a_logger_is_available(): void
    {
        $config = $this->config();

        $entry = $this->createStub(RequestedEntry::class);
        $entry->method('getName')->willReturn('cache.test');

        $logger = $this->createStub(LoggerInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('has')->with(LoggerInterface::class)->willReturn(true);
        $container->expects(self::exactly(2))->method('get')->willReturnMap([
            [
                'config',
                $config,
            ],
            [
                LoggerInterface::class,
                $logger,
            ],
        ]);

        $pool = (new PoolFactory())($container, $entry);

        self::assertInstanceOf(ArrayAdapter::class, $pool);
    }

    #[Test]
    public function it_should_create_an_apcu_pool(): void
    {
        $config                               = $this->config('apcu');
        $config['symfony_cache']['namespace'] = 'factory-test-' . bin2hex(random_bytes(8));

        $pool = (new PoolFactory())($this->container($config), 'cache.test');

        self::assertInstanceOf(ApcuAdapter::class, $pool);
    }

    #[Test]
    public function it_should_create_a_redis_pool(): void
    {
        $connection = $this->createMock(Redis::class);
        $connection->expects(self::once())->method('connect')
            ->with('redis.test', 6379, 5, 'factory-test')->willReturn(true);
        $connection->expects(self::once())->method('setOption')
            ->with(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP)->willReturn(true);

        $factory = $this->createMock(RedisFactory::class);
        $factory->expects(self::once())->method('__invoke')->willReturn($connection);

        $pool = (new PoolFactory())(
            $this->container($this->redisConfig(), redisFactory: $factory),
            'cache.test',
        );

        self::assertInstanceOf(RedisAdapter::class, $pool);
    }

    #[Test]
    #[DataProvider('redisFailures')]
    public function it_should_throw_when_redis_setup_fails(bool $connected, bool $configured, string $message): void
    {
        $connection = $this->createMock(Redis::class);
        $connection->expects(self::once())->method('connect')->willReturn($connected);
        $connection->expects($connected ? self::once() : self::never())
            ->method('setOption')->willReturn($configured);

        $factory = $this->createMock(RedisFactory::class);
        $factory->expects(self::once())->method('__invoke')->willReturn($connection);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        (new PoolFactory())($this->container($this->redisConfig(), redisFactory: $factory), 'cache.test');
    }

    public static function redisFailures(): array
    {
        return [
            'connection failure' => [
                false,
                true,
                'Unable to connect to Redis cache',
            ],
            'serializer failure' => [
                true,
                false,
                'Unable to configure Redis cache serializer',
            ],
        ];
    }

    #[Test]
    public function it_should_reject_an_invalid_redis_factory(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Redis cache factory must be a RedisFactory instance');

        (new PoolFactory())($this->container($this->redisConfig(), redisFactory: new stdClass()), 'cache.test');
    }

    #[Test]
    #[DataProvider('invalidConfigurations')]
    public function it_should_reject_invalid_configuration(array $config, string $message): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($message);

        (new PoolFactory())($this->container($config), 'cache.test');
    }

    public static function invalidConfigurations(): array
    {
        $config = [
            'symfony_cache' => [
                'namespace' => 'test',
                'version'   => '1',
                'pools'     => [
                    'cache.test' => [
                        'adapter'          => 'array',
                        'default_lifetime' => 60,
                    ],
                ],
            ],
        ];

        return [
            'missing cache settings'  => [
                [],
                'Missing Symfony cache configuration',
            ],
            'missing pool'            => [
                array_replace_recursive($config, ['symfony_cache' => ['pools' => ['cache.test' => null]]]),
                'Missing Symfony cache configuration',
            ],
            'invalid settings'        => [
                array_replace_recursive($config, ['symfony_cache' => ['namespace' => '']]),
                'Invalid Symfony cache configuration',
            ],
            'missing version'         => [
                array_replace_recursive($config, ['symfony_cache' => ['version' => '']]),
                'Invalid Symfony cache configuration',
            ],
            'invalid adapter setting' => [
                array_replace_recursive($config, [
                    'symfony_cache' => ['pools' => ['cache.test' => ['adapter' => null]]],
                ]),
                'Invalid Symfony cache configuration',
            ],
            'negative lifetime'       => [
                array_replace_recursive($config, [
                    'symfony_cache' => ['pools' => ['cache.test' => ['default_lifetime' => -1]]],
                ]),
                'Invalid Symfony cache configuration',
            ],
            'missing redis settings'  => [
                array_replace_recursive($config, [
                    'symfony_cache' => ['pools' => ['cache.test' => ['adapter' => 'redis']]],
                ]),
                'Missing Redis cache configuration',
            ],
            'unsupported adapter'     => [
                array_replace_recursive($config, [
                    'symfony_cache' => ['pools' => ['cache.test' => ['adapter' => 'unknown']]],
                ]),
                'Unsupported Symfony cache adapter: unknown',
            ],
        ];
    }

    #[Test]
    public function it_should_reject_an_invalid_logger(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Symfony cache logger must implement LoggerInterface');

        (new PoolFactory())($this->container($this->config(), new stdClass()), 'cache.test');
    }

    private function config(string $adapter = 'array'): array
    {
        return [
            'symfony_cache' => [
                'namespace' => 'test',
                'version'   => '1',
                'pools'     => [
                    'cache.test' => [
                        'adapter'          => $adapter,
                        'default_lifetime' => 60,
                    ],
                ],
            ],
        ];
    }

    private function redisConfig(): array
    {
        $config = $this->config('redis');

        $config['symfony_cache']['pools']['cache.test']['redis'] = [
            'host'          => 'redis.test',
            'port'          => 6379,
            'timeout'       => 5,
            'persistent_id' => 'factory-test',
            'serializer'    => Redis::SERIALIZER_PHP,
        ];

        return $config;
    }

    private function container(
        array $config,
        ?object $logger = null,
        ?object $redisFactory = null,
    ): ContainerInterface {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(LoggerInterface::class)->willReturn($logger !== null);
        $container->method('get')->willReturnMap([
            [
                'config',
                $config,
            ],
            [
                LoggerInterface::class,
                $logger,
            ],
            [
                RedisFactory::class,
                $redisFactory,
            ],
        ]);

        return $container;
    }
}
