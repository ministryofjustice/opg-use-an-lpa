<?php

declare(strict_types=1);

namespace CommonTest\Service\Cache\Symfony;

use Common\Service\Cache\CacheFactory;
use Common\Service\Cache\Symfony\SimpleCacheFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

#[CoversClass(SimpleCacheFactory::class)]
#[UsesClass(CacheFactory::class)]
final class SimpleCacheFactoryTest extends TestCase
{
    #[Test]
    public function it_should_create_a_simple_cache_from_the_request_pool(): void
    {
        $pool      = $this->createStub(CacheItemPoolInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with('cache.request')->willReturn($pool);

        $cache = (new SimpleCacheFactory())($container, CacheInterface::class);

        self::assertInstanceOf(CacheInterface::class, $cache);
    }
}
