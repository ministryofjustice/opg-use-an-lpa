<?php

declare(strict_types=1);

namespace App\Service\Cache;

use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

class CacheFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_requires_a_cache_configuration()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $factory = new CacheFactory($containerProphecy->reveal());

        $this->expectException(RuntimeException::class);
        $cache = $factory->__invoke('a-cache');
    }

    /** @test */
    public function it_requires_a_cache_configuration_for_individual_caches()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->shouldBeCalled()
            ->willReturn(['cache' => []]);

        $factory = new CacheFactory($containerProphecy->reveal());

        $this->expectException(RuntimeException::class);
        $cache = $factory->__invoke('a-cache');
    }

    /** @test */
    public function it_creates_a_configured_cache()
    {
        $cacheAdapterProphecy = $this->prophesize(StorageAdapterFactoryInterface::class);
        $cacheAdapterProphecy
            ->createFromArrayConfiguration(['adapter' => 'memory'])
            ->willReturn($this->prophesize(StorageInterface::class)->reveal());

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy
            ->get('config')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'cache' => [
                        'a-cache' => [
                            'adapter' => 'memory',
                        ],
                    ],
                ]
            );
        $containerProphecy
            ->get(StorageAdapterFactoryInterface::class)
            ->shouldBeCalled()
            ->willReturn($cacheAdapterProphecy->reveal());

        $factory = new CacheFactory($containerProphecy->reveal());

        $cache = $factory->__invoke('a-cache');

        $this->assertInstanceOf(CacheInterface::class, $cache);
        $this->assertInstanceOf(SimpleCacheDecorator::class, $cache);
    }
}
