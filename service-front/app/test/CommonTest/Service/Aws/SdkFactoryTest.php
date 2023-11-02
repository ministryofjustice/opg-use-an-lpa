<?php

declare(strict_types=1);

namespace CommonTest\Service\Aws;

use Aws\Sdk;
use Common\Service\Aws\SdkFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use RuntimeException;

class SdkFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testMissingConfig()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $this->expectException(RuntimeException::class);
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
            ->willReturn(
                [
                'aws' => [],
                ]
            );

        $factory = new SdkFactory();

        $sdk = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(Sdk::class, $sdk);
    }
}
