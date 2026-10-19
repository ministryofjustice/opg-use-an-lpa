<?php

declare(strict_types=1);

namespace AppTest\Service\Cache\Symfony;

use App\Service\Cache\RedisFactory;
use App\Service\Cache\Symfony\PoolFactory;
use DI\Factory\RequestedEntry;
use Laminas\ServiceManager\Factory\FactoryInterface;
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
use Symfony\Component\Cache\Exception\CacheException;
use Throwable;
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
    #[DataProvider('arrayPoolOptions')]
    public function it_should_apply_the_array_pool_serialisation_option(array $options, bool $serialise): void
    {
        $config = $this->config();
        $config['symfony_cache']['pools']['cache.test']['default_lifetime'] = 0;
        $config['symfony_cache']['pools']['cache.test']['options']          = $options;

        $pool        = (new PoolFactory())($this->container($config), 'cache.test');
        $value       = new stdClass();
        $value->name = 'cached value';

        self::assertTrue($pool->save($pool->getItem('object')->set($value)));

        $item = $pool->getItem('object');

        self::assertTrue($item->isHit());
        self::assertEquals($value, $item->get());

        if ($serialise) {
            self::assertNotSame($value, $item->get());
        } else {
            self::assertSame($value, $item->get());
        }
    }

    public static function arrayPoolOptions(): array
    {
        return [
            'serialisation disabled' => [
                ['serialise' => false],
                false,
            ],
            'serialisation enabled'  => [
                ['serialise' => true],
                true,
            ],
            'serialisation omitted'  => [
                [],
                false,
            ],
        ];
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
    #[DataProvider('redisSerializers')]
    public function it_should_create_a_redis_pool(?int $serializer): void
    {
        $config = $this->redisConfig();
        if ($serializer !== null) {
            $config['symfony_cache']['pools']['cache.test']['options']['connection']['serializer'] = $serializer;
        }

        $connection = $this->createMock(Redis::class);
        $connection->expects(self::once())->method('connect')
            ->with('redis.test', 6379, 5, 'factory-test')->willReturn(true);
        $connection->expects(self::once())->method('setOption')
            ->with(Redis::OPT_SERIALIZER, $serializer ?? Redis::SERIALIZER_PHP)->willReturn(true);

        $factory = $this->createMock(FactoryInterface::class);
        $factory->expects(self::once())->method('__invoke')
            ->with(self::isInstanceOf(ContainerInterface::class), Redis::class)->willReturn($connection);

        $pool = (new PoolFactory())($this->container($config, redisFactory: $factory), 'cache.test');

        self::assertInstanceOf(RedisAdapter::class, $pool);
    }

    public static function redisSerializers(): array
    {
        return [
            'default serializer' => [null],
            'PHP serializer'     => [Redis::SERIALIZER_PHP],
            'no serializer'      => [Redis::SERIALIZER_NONE],
        ];
    }

    #[Test]
    #[DataProvider('redisFailures')]
    public function it_should_wrap_redis_setup_failures(bool $connected, bool $configured, string $message): void
    {
        $connection = $this->createMock(Redis::class);
        $connection->expects(self::once())->method('connect')->willReturn($connected);
        $connection->expects($connected ? self::once() : self::never())
            ->method('setOption')->willReturn($configured);

        $factory = $this->createMock(FactoryInterface::class);
        $factory->expects(self::once())->method('__invoke')->willReturn($connection);

        $this->assertCacheException(
            fn () => (new PoolFactory())($this->container($this->redisConfig(), redisFactory: $factory), 'cache.test'),
            RuntimeException::class,
            $message,
        );
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
    public function it_should_reject_an_invalid_redis_connection(): void
    {
        $factory = $this->createMock(FactoryInterface::class);
        $factory->expects(self::once())->method('__invoke')->willReturn(new stdClass());

        $this->assertCacheException(
            fn () => (new PoolFactory())($this->container($this->redisConfig(), redisFactory: $factory), 'cache.test'),
            UnexpectedValueException::class,
            'Redis cache factory must return a Redis connection',
        );
    }

    #[Test]
    public function it_should_preserve_container_failures(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('config')->willThrowException(new RuntimeException('Container failure'));

        $this->assertCacheException(
            fn () => (new PoolFactory())($container, 'cache.test'),
            RuntimeException::class,
            'Container failure',
        );
    }

    #[Test]
    #[DataProvider('invalidConfigurations')]
    public function it_should_reject_invalid_configuration(array $config, string $message): void
    {
        $this->assertCacheException(
            fn () => (new PoolFactory())($this->container($config), 'cache.test'),
            UnexpectedValueException::class,
            $message,
        );
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
                'Missing Symfony cache configuration for cache.test',
            ],
            'missing pool'            => [
                array_replace_recursive($config, ['symfony_cache' => ['pools' => ['cache.test' => null]]]),
                'Missing Symfony cache configuration for cache.test',
            ],
            'invalid settings'        => [
                array_replace_recursive($config, ['symfony_cache' => ['namespace' => '']]),
                'Invalid Symfony cache configuration for cache.test',
            ],
            'missing version'         => [
                array_replace_recursive($config, ['symfony_cache' => ['version' => '']]),
                'Invalid Symfony cache configuration for cache.test',
            ],
            'invalid adapter setting' => [
                array_replace_recursive($config, [
                    'symfony_cache' => ['pools' => ['cache.test' => ['adapter' => null]]],
                ]),
                'Invalid Symfony cache configuration for cache.test',
            ],
            'negative lifetime'       => [
                array_replace_recursive($config, [
                    'symfony_cache' => ['pools' => ['cache.test' => ['default_lifetime' => -1]]],
                ]),
                'Invalid Symfony cache configuration for cache.test',
            ],
            'invalid options'         => [
                array_replace_recursive($config, [
                    'symfony_cache' => ['pools' => ['cache.test' => ['options' => false]]],
                ]),
                'Invalid Symfony cache configuration for cache.test',
            ],
            'invalid serialisation'   => [
                array_replace_recursive($config, [
                    'symfony_cache' => ['pools' => ['cache.test' => ['options' => ['serialise' => 'yes']]]],
                ]),
                'Invalid array cache serialisation option',
            ],
            'missing redis settings'  => [
                array_replace_recursive($config, [
                    'symfony_cache' => ['pools' => ['cache.test' => ['adapter' => 'redis']]],
                ]),
                'Missing Redis cache configuration',
            ],
            'invalid redis settings'  => [
                array_replace_recursive($config, [
                    'symfony_cache' => [
                        'pools' => [
                            'cache.test' => [
                                'adapter' => 'redis',
                                'options' => ['connection' => ['host' => 'redis.test']],
                            ],
                        ],
                    ],
                ]),
                'Invalid Redis cache configuration',
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
        $this->assertCacheException(
            fn () => (new PoolFactory())($this->container($this->config(), new stdClass()), 'cache.test'),
            UnexpectedValueException::class,
            'Symfony cache logger must implement LoggerInterface',
        );
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

        $config['symfony_cache']['pools']['cache.test']['options']['connection'] = [
            'host'          => 'redis.test',
            'port'          => 6379,
            'timeout'       => 5,
            'persistent_id' => 'factory-test',
        ];

        return $config;
    }

    /**
     * Accepts a test callable expecting to fail with a CacheException
     *
     * @param class-string<Throwable> $cause
     */
    private function assertCacheException(callable $build, string $cause, string $message): void
    {
        try {
            $build();
            self::fail('Expected cache pool creation to fail');
        } catch (CacheException $exception) {
            self::assertSame('Unable to build a cache pool for cache.test', $exception->getMessage());
            self::assertInstanceOf($cause, $exception->getPrevious());
            self::assertSame($message, $exception->getPrevious()->getMessage());
        }
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
