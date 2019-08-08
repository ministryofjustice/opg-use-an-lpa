<?php

declare(strict_types=1);

namespace CommonTest\Service\User;

use Common\Entity\User;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client;
use Common\Service\User\UserService;
use DateTime;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    /** @test */
    public function can_authenticate_with_good_credentials()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/auth',
            [
                'email' => 'test@example.com',
                'password' => 'test'
            ])
            ->willReturn([
                'Email'        => 'test@example.com',
                'LastLogin' => '2019-07-10T09:00:00'
            ]);

        $userFactoryCallable = function($identity, $roles, $details) {
            $this->assertEquals('test@example.com', $identity);
            $this->assertIsArray($roles);
            $this->assertIsArray($details);
            $this->assertArrayHasKey('LastLogin', $details);

            return new User($identity, $roles, $details);
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable);

        $return = $service->authenticate('test@example.com', 'test');

        $this->assertInstanceOf(User::class, $return);
        $this->assertEquals('test@example.com', $return->getIdentity());
        $this->assertEquals(new DateTime('2019-07-10T09:00:00'), $return->getDetail('lastLogin'));

    }

    /** @test */
    public function authentication_fails_with_bad_credentials()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/auth',
            [
                'email' => 'test@example.com',
                'password' => 'badpass'
            ])
            ->willThrow(ApiException::create('test'));

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable);

        $return = $service->authenticate('test@example.com', 'badpass');

        $this->assertNull($return);
    }

    /** @test */
    public function bad_datetime_throws_exception()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/auth',
            [
                'email' => 'test@example.com',
                'password' => 'test'
            ])
            ->willReturn([
                'Email'        => 'test@example.com',
                'LastLogin' => 'baddatetime'
            ]);

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable);

        $this->expectException(\RuntimeException::class);
        $return = $service->authenticate('test@example.com', 'test');
    }
}