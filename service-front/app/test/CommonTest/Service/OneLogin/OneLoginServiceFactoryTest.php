<?php

declare(strict_types=1);

namespace Service\OneLogin;

use Common\Service\ApiClient\Client;
use Common\Service\OneLogin\OneLoginService;
use Common\Service\OneLogin\OneLoginServiceFactory;
use Mezzio\Authentication\UserInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class OneLoginServiceFactoryTest extends TestCase
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
            ->get(LoggerInterface::class)
            ->willReturn($this->prophesize(LoggerInterface::class)->reveal());
        $containerProphecy->get(UserInterface::class)
            ->willReturn(function () {
            });

        $factory = new OneLoginServiceFactory();

        $oneLoginService = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(OneLoginService::class, $oneLoginService);
    }
}
