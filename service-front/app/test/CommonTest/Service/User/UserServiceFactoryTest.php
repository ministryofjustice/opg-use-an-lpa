<?php

declare(strict_types=1);

namespace CommonTest\Service\User;

use Common\Service\ApiClient\Client;
use Common\Service\User\UserService;
use Common\Service\User\UserServiceFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Authentication\UserInterface;

class UserServiceFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_a_valid_UserServiceFactory_instance()
    {
        $clientProphecy = $this->prophesize(Client::class);

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->get(Client::class)
            ->willReturn($clientProphecy->reveal());
        $containerProphecy->get(UserInterface::class)
            ->willReturn(function(){});

        $factory = new UserServiceFactory();

        $userFactory = $factory($containerProphecy->reveal());

        $this->assertInstanceOf(UserService::class, $userFactory);
    }
}
