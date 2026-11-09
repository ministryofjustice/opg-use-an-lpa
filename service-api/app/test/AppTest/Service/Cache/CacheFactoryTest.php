<?php

declare(strict_types=1);

namespace AppTest\Service\Cache;

use App\Service\Cache\CacheFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Psr16Cache;
use UnexpectedValueException;

#[CoversClass(CacheFactory::class)]
final class CacheFactoryTest extends TestCase
{
    #[Test]
    public function it_should_wrap_the_named_pool_in_a_psr16_cache(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $pool      = $this->createMock(CacheItemPoolInterface::class);
        $container->expects(self::once())->method('get')->with('cache.one-login')->willReturn($pool);

        $cache = (new CacheFactory($container))('one-login');
        self::assertInstanceOf(Psr16Cache::class, $cache);
        self::assertInstanceOf(Psr16Cache::class, $cache);
    }

    #[Test]
    public function it_should_reject_a_service_that_is_not_a_cache_item_pool(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with('cache.one-login')->willReturn(new stdClass());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('cache.one-login must implement CacheItemPoolInterface');

        (new CacheFactory($container))('one-login');
    }

    #[Test]
    public function it_should_return_the_named_tag_aware_pool(): void
    {
        $pool = $this->createStub(TagAwareAdapterInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with('cache.lpa-in-session')->willReturn($pool);

        self::assertSame($pool, (new CacheFactory($container))->taggable('lpa-in-session'));
    }

    #[Test]
    public function it_should_reject_a_non_tag_aware_pool(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with('cache.lpa-in-session')
            ->willReturn($this->createStub(CacheItemPoolInterface::class));

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('cache.lpa-in-session must implement TagAwareAdapterInterface');

        (new CacheFactory($container))->taggable('lpa-in-session');
    }
}
