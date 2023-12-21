<?php

namespace Service\OneLogin;

use Common\Service\ApiClient\Client;
use Common\Service\OneLogin\OneLoginServiceFactory;
use Mezzio\Authentication\UserInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

class OneLoginServiceFactoryTest
{
    use ProphecyTrait;

    /** @test */
    public function it_creates_an_instance(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy
            ->get(Client::class)
            ->willReturn($this->prophesize(Client::class)->reveal());

        $containerProphecy
            ->get(UserInterface::class)
            ->willReturn($this->prophesize(UserInterface::class)->reveal());

        $containerProphecy
            ->get(LoggerInterface::class)
            ->willReturn($this->prophesize(LoggerInterface::class)->reveal());

        $factory = new OneLoginServiceFactory();

        $oneLoginService = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(OneLoginService::class, $oneLoginService);
    }
}