<?php

declare(strict_types=1);

namespace CommonTest\Service\User;

use Common\Entity\User;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client;
use Common\Service\User\UserService;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    /** @test */
    public function can_create_a_new_user_account()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPost(
            '/v1/user',
            [
                'email' => 'test@example.com',
                'password' => 'test'
            ])
            ->willReturn([
                'Email'           => 'a@b.com',
                'ActivationToken' => 'activate1234567890',
            ]);

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable);

        $return = $service->create('test@example.com', 'test');

        $this->assertIsArray($return);
        $this->assertArrayHasKey('Email', $return);
        $this->assertArrayHasKey('ActivationToken', $return);
    }

    /** @test */
    public function can_get_an_account_by_email()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet(
            '/v1/user',
            [
                'email' => 'test@example.com',
            ])
            ->willReturn([
                'Email' => 'a@b.com',
            ]);

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable);

        $return = $service->getByEmail('test@example.com');

        $this->assertIsArray($return);
        $this->assertArrayHasKey('Email', $return);
    }

    /** @test */
    public function passes_exception_when_user_not_found_by_email()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet(
            '/v1/user',
            [
                'email' => 'test@example.com',
            ])
            ->willThrow(new ApiException('User not found', StatusCodeInterface::STATUS_NOT_FOUND));

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable);

        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectException(ApiException::class);
        $return = $service->getByEmail('test@example.com');
    }

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
    public function bad_datetime_throws_exception_during_authentication()
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

    /** @test */
    public function can_activate_a_user()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/user-activation',
            [
                'activation_token' => 'activate1234567890',
            ])
            ->willReturn([
                'Email' => 'test@example.com'
            ]);

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable);

        $return = $service->activate('activate1234567890');

        $this->assertTrue($return);
    }

    /** @test */
    public function can_activate_a_user_and_return_empty_data()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/user-activation',
            [
                'activation_token' => 'activate1234567890',
            ])
            ->willReturn([]);

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable);

        $return = $service->activate('activate1234567890');

        $this->assertFalse($return);
    }

    /** @test */
    public function whilst_activating_an_unknown_user_false_is_returned()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/user-activation',
            [
                'activation_token' => 'activate1234567890',
            ])
            ->willThrow(new ApiException('User not found', StatusCodeInterface::STATUS_NOT_FOUND));

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable);

        $return = $service->activate('activate1234567890');

        $this->assertFalse($return);
    }

    /** @test */
    public function whilst_activating_a_user_an_exception_can_be_thrown()
    {
        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/user-activation',
            [
                'activation_token' => 'activate1234567890',
            ])
            ->willThrow(new ApiException('Activation exception', StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR));

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable);

        $this->expectExceptionCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $this->expectException(ApiException::class);
        $return = $service->activate('activate1234567890');
    }
}