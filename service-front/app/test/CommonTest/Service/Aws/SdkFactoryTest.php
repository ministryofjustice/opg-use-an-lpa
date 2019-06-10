<?php

declare(strict_types=1);

namespace CommonTest\Service\Aws;

use Common\Service\Aws\SdkFactory;
use Aws\Sdk;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SdkFactoryTest extends TestCase
{
    public function testMissingConfig()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing aws configuration');

        $containerProphecy
            ->get('config')
            ->willReturn([]);

        $factory = new SdkFactory();

        $factory($containerProphecy->reveal());
    }

    public function testValidConfig()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy
            ->get('config')
            ->willReturn([
                'aws' => [],
            ]);

        $factory = new SdkFactory();

        $sdk = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(Sdk::class, $sdk);
    }
}
