<?php

declare(strict_types=1);

namespace AppTest\Service\Aws;

use Aws\Sdk;
use App\Service\Aws\SdkFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SdkFactoryTest extends TestCase
{

    public function testMissingConfig()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing aws configuration');

        //---

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([]);

        $factory = new SdkFactory();

        $factory($container->reveal());
    }

    public function testValidConfig()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn([
            'aws' => [],
        ]);

        $factory = new SdkFactory();

        $sdk = $factory($container->reveal());

        $this->assertInstanceOf(Sdk::class, $sdk);
    }

}
