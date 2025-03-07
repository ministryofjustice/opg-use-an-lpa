<?php

declare(strict_types=1);

namespace CommonTest\Service\User;

use PHPUnit\Framework\Attributes\Test;
use Common\Service\ApiClient\Client;
use Common\Service\User\UserService;
use Common\Service\User\UserServiceFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Mezzio\Authentication\UserInterface;

class UserServiceFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_creates_a_valid_UserServiceFactory_instance(): void
    {
        $clientProphecy = $this->prophesize(Client::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(Client::class)
            ->willReturn($clientProphecy->reveal());
        $containerProphecy->get(UserInterface::class)
            ->willReturn(function () {
            });
        $containerProphecy->get(LoggerInterface::class)
            ->willReturn($loggerProphecy->reveal());

        $factory = new UserServiceFactory();

        $userFactory = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(UserService::class, $userFactory);
    }
}
