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
use Psr\Log\LoggerInterface;
use RuntimeException;

class UserServiceTest extends TestCase
{
    /** @test */
    public function can_create_a_new_user_account()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPost(
            '/v1/user',
            [
                'email' => 'test@example.com',
                'password' => 'test'
            ])
            ->willReturn([
                'Id'              => '12345',
                'Email'           => 'a@b.com',
                'ActivationToken' => 'activate1234567890',
            ]);

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $return = $service->create('test@example.com', 'test');

        $this->assertIsArray($return);
        $this->assertArrayHasKey('Email', $return);
        $this->assertArrayHasKey('ActivationToken', $return);
    }

    /** @test */
    public function can_get_an_account_by_email()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

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

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $return = $service->getByEmail('test@example.com');

        $this->assertIsArray($return);
        $this->assertArrayHasKey('Email', $return);
    }

    /** @test */
    public function passes_exception_when_user_not_found_by_email()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

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

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectException(ApiException::class);
        $return = $service->getByEmail('test@example.com');
    }

    /** @test */
    public function can_authenticate_with_good_credentials()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/auth',
            [
                'email' => 'test@example.com',
                'password' => 'test'
            ])
            ->willReturn([
                'Id'        => '01234567-0123-0123-0123-012345678901',
                'Email'     => 'test@example.com',
                'LastLogin' => '2019-07-10T09:00:00'
            ]);

        $userFactoryCallable = function($identity, $roles, $details) {
            $this->assertEquals('01234567-0123-0123-0123-012345678901', $identity);
            $this->assertIsArray($roles);
            $this->assertIsArray($details);
            $this->assertArrayHasKey('Email', $details);
            $this->assertArrayHasKey('LastLogin', $details);

            return new User($identity, $roles, $details);
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $return = $service->authenticate('test@example.com', 'test');

        $this->assertInstanceOf(User::class, $return);
        $this->assertEquals('01234567-0123-0123-0123-012345678901', $return->getIdentity());
        $this->assertEquals(new DateTime('2019-07-10T09:00:00'), $return->getDetail('lastLogin'));
        $this->assertEquals('test@example.com', $return->getDetail('email'));
    }

    /** @test */
    public function authentication_fails_with_bad_credentials()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

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

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $return = $service->authenticate('test@example.com', 'badpass');

        $this->assertNull($return);
    }

    /** @test */
    public function bad_datetime_throws_exception_during_authentication()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/auth',
            [
                'email' => 'test@example.com',
                'password' => 'test'
            ])
            ->willReturn([
                'Id'        => '01234567-0123-0123-0123-012345678901',
                'Email'     => 'test@example.com',
                'LastLogin' => '2019-07-10T09:00:00'
            ]);

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $this->expectException(RuntimeException::class);
        $return = $service->authenticate('test@example.com', 'test');
    }

    /** @test */
    public function can_activate_a_user()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/user-activation',
            [
                'activation_token' => 'activate1234567890',
            ])
            ->willReturn([
                'Id'    => '12345',
                'Email' => 'test@example.com'
            ]);

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $return = $service->activate('activate1234567890');

        $this->assertTrue($return);
    }

    /** @test */
    public function can_activate_a_user_and_return_empty_data()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

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

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $return = $service->activate('activate1234567890');

        $this->assertFalse($return);
    }

    /** @test */
    public function whilst_activating_an_unknown_user_false_is_returned()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

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

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $return = $service->activate('activate1234567890');

        $this->assertFalse($return);
    }

    /** @test */
    public function whilst_activating_a_user_an_exception_can_be_thrown()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

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

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $this->expectExceptionCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $this->expectException(ApiException::class);
        $return = $service->activate('activate1234567890');
    }

    /** @test */
    public function can_request_a_password_reset_token_for_a_valid_user()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/request-password-reset',
            [
                'email' => 'test@example.com'
            ])
            ->willReturn([
                'Id'                 => '12345',
                'Email'              => 'test@example.com',
                'PasswordResetToken' => 'resettokenAABBCCDDEE'
            ]);

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $token = $service->requestPasswordReset('test@example.com');

        $this->assertEquals('resettokenAABBCCDDEE', $token);
    }

    /** @test */
    public function a_password_reset_request_for_an_invalid_user_will_not_be_found()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/request-password-reset',
            [
                'email' => 'test@example.com'
            ])
            ->willThrow(new ApiException('User not found', StatusCodeInterface::STATUS_NOT_FOUND));

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectException(ApiException::class);
        $token = $service->requestPasswordReset('test@example.com');
    }

    /** @test */
    public function exception_thrown_when_api_gives_invalid_response_to_reset_password_request()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/request-password-reset',
            [
                'email' => 'test@example.com'
            ])
            ->willReturn([
                'InvalidResponse' => 'YouWereExpectingSomethingElse'
            ]);

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $this->expectExceptionCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $this->expectException(RuntimeException::class);
        $token = $service->requestPasswordReset('test@example.com');
    }

    /** @test */
    public function can_delete_a_users_account()
    {
        $id = '01234567-0123-0123-0123-012345678901';
        $email = 'a@b.com';

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpDelete('/v1/delete-account/' . $id)
            ->willReturn([
                'Id'       => $id,
                'Email'    => $email,
                'Password' => password_hash('pa33w0rd123', PASSWORD_DEFAULT),
                'LastLogin'=> null
            ]);

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        // deleteAccount is a void method, so if successful, $result should be null
        $result = $service->deleteAccount($id);

        $this->assertNull($result);
    }

    /** @test */
    public function exception_thrown_when_api_gives_invalid_response_to_delete_account_request()
    {
        $id = '01234567-0123-0123-0123-012345678901';
        $email = 'a@b.com';

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpDelete('/v1/delete-account/' . $id)
            ->willThrow(new ApiException('HTTP: 500 - Unexpected API response', StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR));

        $userFactoryCallable = function($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $this->expectExceptionCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $this->expectException(RuntimeException::class);

        $service->deleteAccount($id);
    }
}
