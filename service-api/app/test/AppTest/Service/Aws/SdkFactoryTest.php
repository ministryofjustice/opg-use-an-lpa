<?php

declare(strict_types=1);

namespace AppTest\Service\Aws;

use Aws\Sdk;
use App\Service\Aws\SdkFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Exception;

class SdkFactoryTest extends TestCase
{
    /** @test */
    public function can_instantiate()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get('config')->willReturn([
            'aws' => []
        ]);

        $factory = new SdkFactory();
        $repo = $factory($containerProphecy->reveal());
        $this->assertInstanceOf(Sdk::class, $repo);
    }

    /** @test */
    public function cannot_instantiate()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get('config')->willReturn([]);

        $factory = new SdkFactory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing aws configuration');

        $factory($containerProphecy->reveal());
    }
}
