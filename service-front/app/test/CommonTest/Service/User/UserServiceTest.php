<?php

declare(strict_types=1);

namespace AppTest\Service\User;

use Common\Entity\User;
use Common\Service\ApiClient\Client;
use Common\Service\User\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function testCanAuthenticateWithGoodCredentials()
    {
        $apiClientProphecy = $this->prophesize(Client::class);

        $service = new UserService($apiClientProphecy->reveal());

        $return = $service->authenticate('test@example.com', 'test');

        $this->assertInstanceOf(User::class, $return);

    }

    public function testAuthenticationFailsWithBadCredentials()
    {
        $apiClientProphecy = $this->prophesize(Client::class);

        $service = new UserService($apiClientProphecy->reveal());

        $return = $service->authenticate('test@example.com', 'badpass');

        $this->assertNull($return);
    }
}