<?php

declare(strict_types=1);

namespace AppTest\Service\User;

use Common\Entity\User;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client;
use Common\Service\User\UserService;
use DateTime;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function testCanAuthenticateWithGoodCredentials()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet(
            '/v1/auth',
            [
                'email' => 'test@example.com',
                'password' => 'test'
            ])
            ->willReturn([
                'id'        => 'guid',
                'lastlogin' => '2019-07-10T09:00:00'
            ]);

        $service = new UserService($apiClientProphecy->reveal());

        $return = $service->authenticate('test@example.com', 'test');

        $this->assertInstanceOf(User::class, $return);
        $this->assertEquals('guid', $return->getId());
        $this->assertEquals(new DateTime('2019-07-10T09:00:00'), $return->getLastSignedIn());

    }

    public function testAuthenticationFailsWithBadCredentials()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet(
            '/v1/auth',
            [
                'email' => 'test@example.com',
                'password' => 'badpass'
            ])
            ->willThrow(ApiException::create('test'));


        $service = new UserService($apiClientProphecy->reveal());

        $return = $service->authenticate('test@example.com', 'badpass');

        $this->assertNull($return);
    }

    public function testBadDateTimeThrowsException()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet(
            '/v1/auth',
            [
                'email' => 'test@example.com',
                'password' => 'test'
            ])
            ->willReturn([
                'id'        => 'guid',
                'lastlogin' => 'baddatetime'
            ]);

        $service = new UserService($apiClientProphecy->reveal());

        $this->expectException(\RuntimeException::class);
        $return = $service->authenticate('test@example.com', 'test');
    }
}