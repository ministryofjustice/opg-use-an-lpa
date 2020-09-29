<?php

declare(strict_types=1);

namespace CommonTest\Service\User;

use App\Exception\BadRequestException;
use App\Exception\ForbiddenException;
use App\Exception\GoneException;
use Common\Entity\User;
use Common\Exception\ApiException;
use Common\Service\ApiClient\Client;
use Common\Service\User\UserService;
use DateTime;
use DI\NotFoundException;
use Fig\Http\Message\StatusCodeInterface;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\ExpectationFailedException;
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
            ]
        )
            ->willReturn([
                'Id'              => '12345',
                'Email'           => 'a@b.com',
                'ActivationToken' => 'activate1234567890',
            ]);

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $return = $service->create('test@example.com', new HiddenString('test'));

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
            ]
        )
            ->willReturn([
                'Email' => 'a@b.com',
            ]);

        $userFactoryCallable = function ($identity, $roles, $details) {
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
            ]
        )
            ->willThrow(new ApiException('User not found', StatusCodeInterface::STATUS_NOT_FOUND));

        $userFactoryCallable = function ($identity, $roles, $details) {
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
            ]
        )
            ->willReturn([
                'Id'        => '01234567-0123-0123-0123-012345678901',
                'Email'     => 'test@example.com',
                'LastLogin' => '2019-07-10T09:00:00'
            ]);

        $userFactoryCallable = function ($identity, $roles, $details) {
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
            ]
        )
            ->willThrow(ApiException::create('test'));

        $userFactoryCallable = function ($identity, $roles, $details) {
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
            ]
        )
            ->willReturn([
                'Id'        => '01234567-0123-0123-0123-012345678901',
                'Email'     => 'test@example.com',
                'LastLogin' => '2019-07-10T09:00:00'
            ]);

        $userFactoryCallable = function ($identity, $roles, $details) {
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
            ]
        )
            ->willReturn([
                'Id'    => '12345',
                'Email' => 'test@example.com'
            ]);

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $return = $service->activate('activate1234567890');

        $this->assertEquals('test@example.com', $return);
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
            ]
        )
            ->willThrow(new ApiException('User not found', StatusCodeInterface::STATUS_NOT_FOUND));

        $userFactoryCallable = function ($identity, $roles, $details) {
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
            ]
        )
            ->willThrow(new ApiException('Activation exception', StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR));

        $userFactoryCallable = function ($identity, $roles, $details) {
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
            ]
        )
            ->willReturn([
                'Id'                 => '12345',
                'Email'              => 'test@example.com',
                'PasswordResetToken' => 'resettokenAABBCCDDEE'
            ]);

        $userFactoryCallable = function ($identity, $roles, $details) {
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
            ]
        )
            ->willThrow(new ApiException('User not found', StatusCodeInterface::STATUS_NOT_FOUND));

        $userFactoryCallable = function ($identity, $roles, $details) {
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
            ]
        )
            ->willReturn([
                'InvalidResponse' => 'YouWereExpectingSomethingElse'
            ]);

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $this->expectExceptionCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $this->expectException(RuntimeException::class);
        $token = $service->requestPasswordReset('test@example.com');
    }

    /** @test */
    public function can_change_password_for_authenticated_user()
    {
        $password1 = new HiddenString('CurrentPassw0rd');
        $password2 = new HiddenString('FinalF0rm');

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/change-password',
            [
                'user-id'       => '01234567-0123-0123-0123-012345678901',
                'password'      => $password1->getString(),
                'new-password'  => $password2->getString()
            ]
        )
            ->willReturn([]);

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());
        $return = $service->changePassword('01234567-0123-0123-0123-012345678901', $password1, $password2);

        $this->assertEmpty($return);
    }

    /** @test */
    public function exception_thrown_when_bad_password_provided_for_change_password_for_authenticated_user()
    {
        $password1 = new HiddenString('BadPassw0rd');
        $password2 = new HiddenString('FinalF0rm');

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/change-password',
            [
                'user-id'       => '01234567-0123-0123-0123-012345678901',
                'password'      => $password1->getString(),
                'new-password'  => $password2->getString()
            ]
        )
            ->willThrow(new ApiException(
                'Authentication failed for user ID 01234567-0123-0123-0123-012345678901',
                StatusCodeInterface::STATUS_FORBIDDEN
            ));

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $this->expectExceptionCode(StatusCodeInterface::STATUS_FORBIDDEN);
        $this->expectException(ApiException::class);
        $return = $service->changePassword('01234567-0123-0123-0123-012345678901', $password1, $password2);
    }

    /** @test */
    public function exception_thrown_when_user_not_found_for_change_password_for_authenticated_user()
    {
        $password1 = new HiddenString('BadPassw0rd');
        $password2 = new HiddenString('FinalF0rm');

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/change-password',
            [
                'user-id'       => '01234567-9999-9999-9999-012345678901',
                'password'      => $password1->getString(),
                'new-password'  => $password2->getString()
            ]
        )
            ->willThrow(new ApiException('User not found', StatusCodeInterface::STATUS_NOT_FOUND));

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectException(ApiException::class);
        $return = $service->changePassword('01234567-9999-9999-9999-012345678901', $password1, $password2);
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
                'LastLogin' => null
            ]);

        $userFactoryCallable = function ($identity, $roles, $details) {
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

        $this->expectExceptionCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $this->expectException(RuntimeException::class);

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $service->deleteAccount($id);
    }

    /** @test */
    public function can_request_email_reset()
    {
        $password = new HiddenString('pa33W0rd');
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/request-change-email',
            [
                'user-id'       => '12345',
                'new-email'     => 'new@email.com',
                'password'      => $password->getString(),
            ]
        )->willReturn([
                'EmailResetExpiry' => time() + (60 * 60 * 48),
                'Email'            => 'old@email.com',
                'LastLogin'        => null,
                'Id'               => '12345',
                'NewEmail'         => 'new@email.com',
                'EmailResetToken'  => 't0ken12345',
                'Password'         => $password->getString(),
            ]);

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $data = $service->requestChangeEmail('12345', 'new@email.com', $password);

        $this->assertEquals('12345', $data['Id']);
        $this->assertEquals('old@email.com', $data['Email']);
        $this->assertEquals('new@email.com', $data['NewEmail']);
        $this->assertEquals($password, $data['Password']);
        $this->assertEquals('t0ken12345', $data['EmailResetToken']);
        $this->assertArrayHasKey('EmailResetExpiry', $data);
    }

    /** @test */
    public function exception_thrown_when_user_id_not_provided_in_request_email_change()
    {
        $password = new HiddenString('pa33W0rd');

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/request-change-email',
            [
                'user-id'       => '',
                'new-email'     => 'new@email.com',
                'password'      => $password->getString(),
            ]
        )->willThrow(new ApiException('User Id must be provided', StatusCodeInterface::STATUS_BAD_REQUEST));

        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectException(ApiException::class);

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $service->requestChangeEmail('', 'new@email.com', $password);
    }

    /** @test */
    public function exception_thrown_when_new_email_not_provided_in_request_email_change()
    {
        $password = new HiddenString('pa33W0rd');
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/request-change-email',
            [
                'user-id'       => '12345',
                'new-email'     => '',
                'password'      => $password->getString(),
            ]
        )->willThrow(new ApiException('New email address must be provided', StatusCodeInterface::STATUS_BAD_REQUEST));

        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectException(ApiException::class);

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $service->requestChangeEmail('12345', '', $password);
    }

    /** @test */
    public function exception_thrown_when_password_not_provided_in_request_email_change()
    {
        $password = new HiddenString('');
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/request-change-email',
            [
                'user-id'       => '12345',
                'new-email'     => 'new@email.com',
                'password'      =>  $password->getString(),
            ]
        )->willThrow(new ApiException('Current password must be provided', StatusCodeInterface::STATUS_BAD_REQUEST));

        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectException(ApiException::class);

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $service->requestChangeEmail('12345', 'new@email.com', $password);
    }

    /** @test */
    public function can_reset_email_function_returns_true_when_successful()
    {
        $resetToken = 't0ken12345';
        $userId = '12345';
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet(
            '/v1/can-reset-email',
            [
                'token' => $resetToken,
            ]
        )->willReturn(['Id' => $userId]);

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $canReset = $service->canResetEmail($resetToken);

        $this->assertTrue($canReset);
    }

    /** @test */
    public function can_reset_email_function_returns_false_when_token_expired_or_not_found()
    {
        $resetToken = 't0ken12345';
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet(
            '/v1/can-reset-email',
            [
                'token' => $resetToken,
            ]
        )->willThrow(new ApiException('Email reset token has expired', StatusCodeInterface::STATUS_GONE));

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $result = $service->canResetEmail($resetToken);
        $this->assertFalse($result);
    }

    /** @test */
    public function can_reset_email_function_throws_anything_other_than_a_gone_exception()
    {
        $resetToken = 't0ken12345';
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpGet(
            '/v1/can-reset-email',
            [
                'token' => $resetToken,
            ]
        )->willThrow(new ApiException('Email reset token has expired', StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR));

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $this->expectExceptionCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $this->expectException(ApiException::class);
        $service->canResetEmail($resetToken);
    }

    /** @test */
    public function complete_change_email_returns_nothing_when_successful()
    {
        $resetToken = 't0ken12345';
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $apiClientProphecy = $this->prophesize(Client::class);
        $apiClientProphecy->httpPatch(
            '/v1/complete-change-email',
            [
                'reset_token' => $resetToken,
            ]
        )->willReturn([]);

        $userFactoryCallable = function ($identity, $roles, $details) {
            // Not returning a user here since it shouldn't be called.
            $this->fail('User should not be created');
        };

        $service = new UserService($apiClientProphecy->reveal(), $userFactoryCallable, $loggerProphecy->reveal());

        $result = $service->completeChangeEmail($resetToken);
        $this->assertNull($result);
    }
}
