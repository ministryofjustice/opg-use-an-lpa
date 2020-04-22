<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use Common\Service\Security\RateLimitService;
use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RateLimitServiceTest extends TestCase
{
    /** @test */
    public function its_name_comes_from_the_cache_definition()
    {
        $adapterProphecy = $this->prophesize(AdapterOptions::class);
        $adapterProphecy->getNamespace()->willReturn('cache-namespace-name');

        $storageProphecy = $this->prophesize(StorageInterface::class);
        $storageProphecy
            ->getOptions()
            ->willReturn($adapterProphecy->reveal());

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $rateLimitService = $this->getMockForAbstractClass(
            RateLimitService::class,
            [
                $storageProphecy->reveal(),
                60,
                4,
                $loggerProphecy->reveal()
            ]
        );

        $name = $rateLimitService->getName();

        $this->assertEquals('cache-namespace-name', $name);
    }
}
