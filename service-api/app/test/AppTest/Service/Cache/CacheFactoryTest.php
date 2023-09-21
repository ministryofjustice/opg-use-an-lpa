<?php

declare(strict_types=1);

namespace AppTest\Service\Cache;

use App\Service\Cache\CacheFactory;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Storage\Capabilities;
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
    public function itRequiresACacheConfiguration()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $factory = new CacheFactory($containerProphecy->reveal());

        $this->expectException(RuntimeException::class);
        $cache = $factory->__invoke('a-cache');
    }

    /** @test */
    public function itRequiresACacheConfigurationForIndividualCaches()
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
    public function itCreatesAConfiguredCache()
    {
        $cacheAdapterProphecy = $this->prophesize(StorageAdapterFactoryInterface::class);
        $storageInterfacePropecy = $this->prophesize(StorageInterface::class);
        $capabilitiesPropecy = $this->prophesize(Capabilities::class);

        // Mocking the scenario where all required types are supported with allowed values
        $capabilitiesPropecy->getSupportedDatatypes()->willReturn([
              'string' => true,
              'integer' => true,
              'double' => true,
              'boolean' => true,
              'NULL' => true,
              'array' => true,
              'object' => true]);

        $capabilitiesPropecy->getMaxKeyLength()->willReturn(128);
        $capabilitiesPropecy->getStaticTtl()->willReturn(true);
        $capabilitiesPropecy->getMinTtl()->willReturn(0);

        $storageInterfacePropecy->getCapabilities()->willReturn($capabilitiesPropecy->reveal());

        $cacheAdapterProphecy
            ->createFromArrayConfiguration(['adapter' => 'memory'])
            ->willReturn($storageInterfacePropecy->reveal());

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
