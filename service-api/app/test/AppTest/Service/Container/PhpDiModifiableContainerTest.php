<?php

declare(strict_types=1);

namespace AppTest\Service\Container;

use App\Service\Container\PhpDiModifiableContainer;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class PhpDiModifiableContainerTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_only_acts_on_a_phpdi_container(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $di = new PhpDiModifiableContainer($containerProphecy->reveal());
    }

    /** @test */
    public function it_decorates_set_on_a_phpdi_container(): void
    {
        $containerProphecy = $this->prophesize(Container::class);
        $containerProphecy->set('test', 'test')->shouldBeCalled();

        $di = new PhpDiModifiableContainer($containerProphecy->reveal());

        $di->setValue('test', 'test');
    }
}
