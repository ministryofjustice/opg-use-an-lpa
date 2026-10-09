<?php

declare(strict_types=1);

namespace AppTest\Service\Cache\Symfony;

use App\Service\Cache\Symfony\ConfigProvider;
use App\Service\Cache\Symfony\PoolFactory;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use stdClass;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use UnexpectedValueException;

final class ConfigProviderTest extends TestCase
{
    private string $namespace;

    /** @var list<CacheItemPoolInterface> */
    private array $pools = [];

    protected function setUp(): void
    {
        $this->namespace = 'opg-symfony-cache-test-' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        foreach ($this->pools as $pool) {
            $pool->clear();
        }
    }

    #[Test]
    public function it_bootstraps_shared_pools_and_contract_aliases_without_replacing_psr16(): void
    {
        self::assertSame('apcu', (new ConfigProvider())()['symfony_cache']['pools']['cache.app']['adapter']);
        $container = $this->container();
        $request   = $container->get('cache.request');

        self::assertInstanceOf(ArrayAdapter::class, $request);
        self::assertSame($request, $container->get('cache.request'));
        self::assertSame($request, $container->get(CacheItemPoolInterface::class));
        self::assertSame($request, $container->get(CacheInterface::class));
        self::assertFalse($container->has('cache.system'));
        self::assertFalse($container->has('cache.default_marshaller'));
        self::assertInstanceOf(TagAwareAdapter::class, $container->get(TagAwareCacheInterface::class));
        self::assertSame(
            $container->get('cache.request.taggable'),
            $container->get(TagAwareCacheInterface::class)
        );
        self::assertFalse($container->has(SimpleCacheInterface::class));
    }

    #[Test]
    public function it_shares_the_app_pool_across_containers_but_not_the_request_pool(): void
    {
        if (!ApcuAdapter::isSupported() || !apcu_enabled()) {
            self::markTestSkipped('APCu is not enabled for this runtime');
        }

        $container = $this->container();
        $app       = $container->get('cache.app');
        $request   = $container->get('cache.request');

        self::assertInstanceOf(ApcuAdapter::class, $app);
        self::assertNotSame($request, $app);
        self::assertSame(0, (new ConfigProvider())()['symfony_cache']['pools']['cache.app']['default_lifetime']);
        self::assertTrue($app->save($app->getItem('example')->set('app')));
        self::assertTrue($request->save($request->getItem('example')->set('request')));

        $next = $this->container();
        self::assertSame('app', $next->get('cache.app')->getItem('example')->get());
        self::assertFalse($next->get('cache.request')->getItem('example')->isHit());
    }

    #[Test]
    public function it_injects_an_optional_logger_into_the_array_adapter(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');
        $pool = $this->container(
            ['pools' => ['cache.app' => ['adapter' => 'array']]],
            [],
            [LoggerInterface::class => $logger],
        )->get('cache.app');

        self::assertFalse($pool->save($pool->getItem('unserializable')->set(static fn () => null)));
    }

    #[Test]
    #[DataProvider('adapters')]
    public function it_creates_working_pools(string $adapter): void
    {
        if ($adapter === 'apcu' && (!ApcuAdapter::isSupported() || !apcu_enabled())) {
            self::markTestSkipped('APCu is not enabled for this runtime');
        }

        $container = $this->container([
            'pools' => ['cache.app' => ['adapter' => $adapter]],
        ]);
        $pool      = $container->get('cache.app');
        $item      = $pool->getItem('example');
        $item->set('cached value');

        self::assertTrue($pool->save($item));
        self::assertSame('cached value', $pool->getItem('example')->get());
        self::assertTrue($pool->deleteItem('example'));
        self::assertFalse($pool->getItem('example')->isHit());
    }

    public static function adapters(): array
    {
        return [
            'APCu'  => ['apcu'],
            'array' => ['array'],
        ];
    }

    #[Test]
    public function it_rejects_explicit_apcu_selection_when_apcu_is_disabled(): void
    {
        if (ApcuAdapter::isSupported() && apcu_enabled()) {
            self::markTestSkipped('APCu is enabled for this runtime');
        }

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('APCu cache adapter requires APCu to be enabled for this runtime');

        $this->container(['pools' => ['cache.app' => ['adapter' => 'apcu']]])->get('cache.app');
    }

    #[Test]
    public function it_supports_contract_callbacks_and_tag_invalidation(): void
    {
        $container = $this->container();
        $pool      = $container->get(TagAwareCacheInterface::class);
        $calls     = 0;
        $callback  = static function (ItemInterface $item) use (&$calls): int {
            $item->tag('example-tag');

            return ++$calls;
        };

        self::assertSame(1, $pool->get('example', $callback));
        self::assertSame(1, $pool->get('example', $callback));
        self::assertTrue($pool->invalidateTags(['example-tag']));
        self::assertSame(2, $pool->get('example', $callback));
    }

    #[Test]
    public function it_wraps_the_explicit_app_pool_for_tag_aware_caching(): void
    {
        $container = $this->container(['pools' => ['cache.app' => ['adapter' => 'array']]]);
        $pool      = $container->get('cache.app.taggable');
        $app       = $container->get('cache.app');

        self::assertInstanceOf(TagAwareAdapter::class, $pool);
        self::assertNotSame($pool, $container->get(TagAwareCacheInterface::class));
        self::assertTrue($pool->save($pool->getItem('example')->set('app')));
        self::assertTrue($app->getItem('example')->isHit());
        self::assertSame('app', $pool->getItem('example')->get());
        self::assertFalse($container->get('cache.request')->getItem('example')->isHit());
    }

    #[Test]
    public function it_invalidates_apcu_data_when_the_version_changes(): void
    {
        if (!ApcuAdapter::isSupported() || !apcu_enabled()) {
            self::markTestSkipped('APCu is not enabled for this runtime');
        }

        $overrides = ['pools' => ['cache.app' => ['adapter' => 'apcu']]];
        $pool      = $this->container($overrides)->get('cache.app');
        self::assertTrue($pool->save($pool->getItem('example')->set('value')));

        $updated = $this->container($overrides + ['version' => '2'])->get('cache.app');
        self::assertFalse($updated->getItem('example')->isHit());
        self::assertTrue($updated->clear());
    }

    #[Test]
    public function it_isolates_named_pools_and_application_namespaces_in_shared_storage(): void
    {
        if (!ApcuAdapter::isSupported() || !apcu_enabled()) {
            self::markTestSkipped('APCu is not enabled for this runtime');
        }

        $container = $this->container([
            'pools' => [
                'cache.other' => [
                    'adapter'          => 'apcu',
                    'default_lifetime' => 0,
                ],
            ],
        ], ['cache.other' => PoolFactory::class]);
        $app       = $container->get('cache.app');
        self::assertTrue($app->save($app->getItem('same-key')->set('app')));

        self::assertFalse($container->get('cache.other')->getItem('same-key')->isHit());
        self::assertSame('app', $this->container()->get('cache.app')->getItem('same-key')->get());
        self::assertFalse(
            $this->container(['namespace' => 'another-app'])->get('cache.app')->getItem('same-key')->isHit()
        );
    }

    #[Test]
    public function it_applies_the_configured_default_lifetime(): void
    {
        $pool = $this->container([
            'pools' => [
                'cache.app' => [
                    'adapter'          => 'array',
                    'default_lifetime' => 1,
                ],
            ],
        ])->get('cache.app');
        self::assertInstanceOf(ArrayAdapter::class, $pool);
        self::assertTrue($pool->save($pool->getItem('example')->set('value')));
        self::assertTrue($pool->getItem('example')->isHit());

        sleep(2);

        self::assertFalse($pool->getItem('example')->isHit());
    }

    #[Test]
    public function it_keeps_array_pools_instance_local_and_serializes_objects(): void
    {
        $overrides   = ['pools' => ['cache.app' => ['adapter' => 'array']]];
        $pool        = $this->container($overrides)->get('cache.app');
        $value       = new stdClass();
        $value->name = 'original';

        self::assertTrue($pool->save($pool->getItem('example')->set($value)));
        $value->name = 'changed';

        self::assertSame('original', $pool->getItem('example')->get()->name);
        self::assertFalse($this->container($overrides)->get('cache.app')->getItem('example')->isHit());
    }

    #[Test]
    #[DataProvider('invalidConfigurations')]
    public function it_rejects_invalid_configuration(array $overrides, string $message): void
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage($message);

        $this->container($overrides)->get('cache.app');
    }

    public static function invalidConfigurations(): array
    {
        return [
            'missing pool'         => [
                ['pools' => ['cache.app' => null]],
                'Missing Symfony cache configuration for cache.app',
            ],
            'empty namespace'      => [
                ['namespace' => ''],
                'Invalid Symfony cache configuration for cache.app',
            ],
            'invalid version'      => [
                ['version' => 1],
                'Invalid Symfony cache configuration for cache.app',
            ],
            'empty version'        => [
                ['version' => ''],
                'Invalid Symfony cache configuration for cache.app',
            ],
            'negative lifetime'    => [
                ['pools' => ['cache.app' => ['default_lifetime' => -1]]],
                'Invalid Symfony cache configuration for cache.app',
            ],
            'non-integer lifetime' => [
                ['pools' => ['cache.app' => ['default_lifetime' => '60']]],
                'Invalid Symfony cache configuration for cache.app',
            ],
            'unknown adapter'      => [
                ['pools' => ['cache.app' => ['adapter' => 'unknown']]],
                'Unsupported Symfony cache adapter: unknown',
            ],
            'filesystem adapter'   => [
                ['pools' => ['cache.app' => ['adapter' => 'filesystem']]],
                'Unsupported Symfony cache adapter: filesystem',
            ],
            'system adapter'       => [
                ['pools' => ['cache.app' => ['adapter' => 'system']]],
                'Unsupported Symfony cache adapter: system',
            ],
        ];
    }

    #[Test]
    public function it_requires_configuration(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Missing Symfony cache configuration for cache.app');

        (new PoolFactory())(new ServiceManager(['services' => ['config' => []]]), 'cache.app');
    }

    #[Test]
    public function it_rejects_an_invalid_logger_service(): void
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('Symfony cache logger must implement LoggerInterface');

        $this->container([], [], [LoggerInterface::class => new stdClass()])->get('cache.app');
    }

    #[Test]
    public function it_rejects_an_invalid_app_pool_for_the_tag_aware_wrapper(): void
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('cache.app must implement CacheItemPoolInterface');

        $this->container([], [], ['cache.app' => new stdClass()])->get('cache.app.taggable');
    }

    private function container(array $overrides = [], array $factories = [], array $services = []): ServiceManager
    {
        $config                    = (new ConfigProvider())();
        $config['symfony_cache']   = array_replace_recursive(
            $config['symfony_cache'],
            [
                'namespace' => $this->namespace,
            ],
            $overrides,
        );
        $dependencies              = $config['dependencies'];
        $dependencies['factories'] = array_replace($dependencies['factories'], $factories);
        $dependencies['services']  = ['config' => $config] + $services;

        foreach (array_keys($config['symfony_cache']['pools']) as $name) {
            $dependencies['delegators'][$name][] = function (
                ServiceManager $container,
                string $name,
                callable $callback,
            ): mixed {
                $pool = $callback();
                if ($pool instanceof CacheItemPoolInterface) {
                    $this->pools[] = $pool;
                }

                return $pool;
            };
        }

        return new ServiceManager($dependencies);
    }
}
