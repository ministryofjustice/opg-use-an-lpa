<?php

declare(strict_types=1);

namespace AppTest\Service\Cache;

use App\ConfigProvider as AppConfigProvider;
use App\Service\Cache\CacheFactory;
use App\Service\Cache\Symfony\ConfigProvider;
use Elie\PHPDI\Config\Config;
use Elie\PHPDI\Config\ContainerFactory;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\ConfigProvider as MezzioConfigProvider;
use Mezzio\Router\ConfigProvider as RouterConfigProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use stdClass;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use UnexpectedValueException;

final class CacheFactoryTest extends TestCase
{
    #[Test]
    public function it_adapts_the_named_pool_to_psr16_without_changing_its_storage(): void
    {
        $pool      = new ArrayAdapter(60, true);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with('cache.one-login')->willReturn($pool);

        $cache = (new CacheFactory($container))('one-login');

        self::assertInstanceOf(Psr16Cache::class, $cache);
        self::assertTrue($cache->set('example', 'value'));
        self::assertSame('value', $pool->getItem('example')->get());
        self::assertTrue($cache->delete('example'));
        self::assertFalse($pool->getItem('example')->isHit());
    }

    #[Test]
    public function it_rejects_services_that_are_not_psr6_pools(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn(new stdClass());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('cache.one-login must implement CacheItemPoolInterface');

        (new CacheFactory($container))('one-login');
    }

    #[Test]
    public function it_applies_explicit_expiry_through_the_psr16_bridge(): void
    {
        $pool  = new ArrayAdapter(60, true);
        $cache = (new CacheFactory(new ServiceManager([
            'services' => ['cache.one-login' => $pool],
        ])))('one-login');

        self::assertTrue($cache->set('expired', 'value', 0));
        self::assertFalse($cache->has('expired'));
        self::assertTrue($cache->set('retained', 'value', 3600));
        self::assertSame('value', $cache->get('retained'));
    }

    #[Test]
    #[DataProvider('adapters')]
    public function it_wires_the_application_configuration_in_the_real_phpdi_container(string $adapter): void
    {
        if ($adapter === 'apcu' && (!ApcuAdapter::isSupported() || !apcu_enabled())) {
            self::markTestSkipped('APCu is not enabled for this runtime');
        }

        $config                               = (new ConfigProvider())();
        $environment                          = require __DIR__ . '/../../../../config/autoload/envs.global.php';
        $config                               = array_replace_recursive($config, $environment);
        $config['symfony_cache']['namespace'] = 'migration-test-' . bin2hex(random_bytes(8));
        foreach ($config['symfony_cache']['pools'] as $name => &$pool) {
            if ($name !== 'cache.request') {
                $pool['adapter'] = $adapter;
            }
        }
        unset($pool);
        $config    = array_replace_recursive(
            $config,
            (new RouterConfigProvider())(),
            (new MezzioConfigProvider())(),
            (new AppConfigProvider())(),
        );
        $container = (new ContainerFactory())(new Config($config));

        $factory = $container->get(CacheFactory::class);
        foreach (['one-login' => 60, 'system-message' => 300, 'lpa-data-store' => 3600] as $name => $ttl) {
            self::assertSame($ttl, $config['symfony_cache']['pools']['cache.' . $name]['default_lifetime']);
            $cache = $factory($name);
            self::assertInstanceOf(Psr16Cache::class, $cache);
            self::assertTrue($cache->set('example', $name));
            self::assertSame($name, $container->get('cache.' . $name)->getItem('example')->get());
            self::assertSame($name, $factory($name)->get('example'));
            self::assertTrue($container->get('cache.' . $name)->clear());
        }
        self::assertInstanceOf(CacheInterface::class, $container->get(CacheInterface::class));
        self::assertInstanceOf(CacheItemPoolInterface::class, $container->get(CacheItemPoolInterface::class));
        self::assertInstanceOf(
            TagAwareCacheInterface::class,
            $container->get(TagAwareCacheInterface::class)
        );
        self::assertInstanceOf(
            TagAwareCacheInterface::class,
            $container->get('cache.app.taggable')
        );
        self::assertFalse($container->get(CacheInterface::class)->has('example'));
    }

    public static function adapters(): array
    {
        return [
            'array' => ['array'],
            'APCu'  => ['apcu'],
        ];
    }
}
