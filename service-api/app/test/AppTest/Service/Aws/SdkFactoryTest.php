<?php

declare(strict_types=1);

namespace AppTest\Service\Aws;

use App\Service\Aws\SdkFactory;
use Aws\Sdk;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class SdkFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function can_instantiate(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get('config')->willReturn([
            'aws' => [],
        ]);

        $factory = new SdkFactory();
        $repo    = $factory($containerProphecy->reveal());
        $this->assertInstanceOf(Sdk::class, $repo);
    }

    #[Test]
    public function cannot_instantiate(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy->get('config')->willReturn([]);

        $factory = new SdkFactory();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing aws configuration');

        $factory($containerProphecy->reveal());
    }
}
