<?php

declare(strict_types=1);

namespace AppTest\Service\User;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\BadRequestException;
use App\Exception\ConflictException;
use App\Exception\ForbiddenException;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Exception\UnauthorizedException;
use App\Service\User\UserService;
use DateTime;
use Exception;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Class UserServiceTest
 * @package AppTest\Service\User
 */
class UserServiceTest extends TestCase
{
    // Password hash for password 'test' generated using PASSWORD_DEFAULT
    const PASS = 'test';
    const PASS_HASH = '$2y$10$Ew4y5jzm6fGKAB16huUw6ugZbuhgW5cvBQ6DGVDFzuyBXsCw51dzq';

    /** @test */
    public function can_create_a_valid_instance()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->assertInstanceOf(UserService::class, $us);
    }

    /** @test */
    public function can_add_a_new_user()
    {
        $id = '12345678-1234-1234-1234-123456789012';
        $email = 'a@b.com';
        $password = 'password1';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy->exists($email)->willReturn(false);

        $repoProphecy
            ->getUserByNewEmail($email)
            ->willReturn([])
            ->shouldBeCalled();

        $repoProphecy
            ->add(
                Argument::that(function (string $data) {
                    return Uuid::isValid($data);
                }),
                Argument::exact($email),
                Argument::exact($password),
                Argument::type('string'),
                Argument::type('int')
            )
            ->willReturn(
                [
                    'Id' => $id,
                    'Email' => $email
                ]
            );

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $return = $us->add(['email' => $email, 'password' => $password]);

        $this->assertEquals(['Id' => $id, 'Email' => $email], $return);
    }

    /** @test */
    public function can_reset_existing_user_for_add()
    {
        $id = '12345678-1234-1234-1234-123456789012';
        $email = 'a@b.com';
        $password = 'password1';
        $activationToken = 'activationToken1';
        $ttl = (new DateTime('+24 hours'))->getTimestamp();
        $userData = ['email' => $email, 'password' => $password, 'id' => $id];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy->exists($userData['email'])
            ->willReturn(true);
        $repoProphecy->getByEmail($userData['email'])
            ->willReturn([
                'Id' => $id,
                'Email' => $email,
                'ExpiresTTL' => $ttl,
                'ActivationToken' => $activationToken
            ]);
        $repoProphecy->resetActivationDetails($id,$password, Argument::type('integer'))
            ->willReturn([
                'Id' => $id,
                'Email' => $email
            ]);

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $return = $us->add($userData);

        $this->assertEquals(['Id' => $id, 'Email' => $email], $return);
    }

    /** @test
     * @throws Exception
     */
    public function cannot_add_existing_user_already_activated()
    {
        $id = '12345678-1234-1234-1234-123456789012';
        $email = 'a@b.com';
        $password = 'password1';
        $userData = ['email' => 'a@b.com', 'password' => $password];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy->exists($userData['email'])
            ->willReturn(true);

        $repoProphecy->getByEmail($userData['email'])
            ->willReturn([
                'Id' => $id,
                'Email' => $email
            ]);
        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(ConflictException::class);
        $us->add($userData);
    }

    /** @test */
    public function can_get_a_user_from_storage()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy->getByEmail('a@b.com')
            ->willReturn(['Email' => 'a@b.com', 'Password' => self::PASS_HASH]);

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $return = $us->getByEmail('a@b.com');

        $this->assertEquals(['Email' => 'a@b.com', 'Password' => self::PASS_HASH], $return);
    }

    /** @test */
    public function cannot_retrieve_a_user_that_doesnt_exist()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy->getByEmail('a@b.com')
            ->willThrow(new NotFoundException());

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(NotFoundException::class);
        $return = $us->getByEmail('a@b.com');
    }

    /** @test */
    public function can_authenticate_a_user_with_valid_credentials()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy->getByEmail('a@b.com')
            ->willReturn(['Id' => '1234-1234-1234', 'Email' => 'a@b.com', 'Password' => self::PASS_HASH, 'LastLogin' => '2020-01-01']);
        $repoProphecy->recordSuccessfulLogin('1234-1234-1234', Argument::that(function ($dateTime) {
            $this->assertIsString($dateTime);

            $date = new DateTime($dateTime);
            $this->assertInstanceOf(DateTime::class, $date);

            return true;
        }));

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $return = $us->authenticate('a@b.com', self::PASS);

        $this->assertEquals(['Id' => '1234-1234-1234', 'Email' => 'a@b.com', 'Password' => self::PASS_HASH, 'LastLogin' => '2020-01-01'], $return);
    }

    /** @test */
    public function will_not_authenticate_invalid_credentials()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy->getByEmail('a@b.com')
            ->willReturn(['Email' => 'a@b.com', 'Password' => self::PASS_HASH]);

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(ForbiddenException::class);
        $return = $us->authenticate('a@b.com', 'badpassword');
    }

    /** @test */
    public function will_not_authenticate_unknown_user()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy->getByEmail('baduser@b.com')
            ->willThrow(new NotFoundException());

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(NotFoundException::class);
        $return = $us->authenticate('baduser@b.com', self::PASS);
    }

    /** @test */
    public function will_not_authenticate_unverfied_account()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy->getByEmail('a@b.com')
            ->willReturn(['Email' => 'a@b.com', 'Password' => self::PASS_HASH, 'ActivationToken' => 'aToken', 'Id' => '1234-1234-1234']);

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(UnauthorizedException::class);
        $return = $us->authenticate('a@b.com', self::PASS);
    }

    /** @test */
    public function will_generate_and_record_a_password_reset_token()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->recordPasswordResetRequest('a@b.com', Argument::type('string'), Argument::type('int'))
            ->willReturn([
                'Email' => 'a@b.com',
                'PasswordResetToken' => 'resetTokenAABBCCDDEE',
                'Id' => '1234-1234-1234'
            ]);

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $return = $us->requestPasswordReset('a@b.com');

        $this->assertIsArray($return);
        $this->assertArrayHasKey('PasswordResetToken', $return);
        $this->assertIsString($return['PasswordResetToken']);
    }

    /** @test */
    public function will_reset_a_password_given_a_valid_token()
    {
        $token = 'RESET_TOKEN_123';
        $password = 'newpassword';
        $id = '12345-1234-1234-1234-12345';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->getIdByPasswordResetToken($token)
            ->willReturn($id)
            ->shouldBeCalled();

        $repoProphecy
            ->get($id)
            ->willReturn([
                'Id' => $id,
                'PasswordResetToken' => $token,
                'PasswordResetExpiry' => (new DateTime('+1 week'))->format('U')
            ])
            ->shouldBeCalled();

        $repoProphecy
            ->resetPassword($id, $password)
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $us->completePasswordReset($token, new HiddenString($password));
    }

    /** @test */
    public function will_not_reset_password_with_expired_token()
    {
        $token = 'RESET_TOKEN_123';
        $password = 'newpassword';
        $id = '12345-1234-1234-1234-12345';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->getIdByPasswordResetToken($token)
            ->willReturn($id)
            ->shouldBeCalled();

        $repoProphecy
            ->get($id)
            ->willReturn([
                'Id' => $id,
                'PasswordResetToken' => $token,
                'PasswordResetExpiry' => (new DateTime('-1 week'))->format('U')
            ])
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(BadRequestException::class);
        $us->completePasswordReset($token, new HiddenString($password));
    }

    /** @test */
    public function will_confirm_valid_password_reset_token()
    {
        $token = 'RESET_TOKEN_123';
        $id = '12345-1234-1234-1234-12345';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->getIdByPasswordResetToken($token)
            ->willReturn($id)
            ->shouldBeCalled();

        $repoProphecy
            ->get($id)
            ->willReturn([
                'Id' => $id,
                'PasswordResetToken' => $token,
                'PasswordResetExpiry' => (new DateTime('+1 week'))->format('U')
            ])
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $result = $us->canResetPassword($token);

        $this->assertEquals($id, $result);
    }

    /** @test */
    public function will_reject_expired_password_reset_token()
    {
        $token = 'RESET_TOKEN_123';
        $id = '12345-1234-1234-1234-12345';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->getIdByPasswordResetToken($token)
            ->willReturn($id)
            ->shouldBeCalled();

        $repoProphecy
            ->get($id)
            ->willReturn([
                'Id' => $id,
                'PasswordResetToken' => $token,
                'PasswordResetExpiry' => (new DateTime('-1 week'))->format('U')
            ])
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(GoneException::class);
        $result = $us->canResetPassword($token);
    }

    /** @test */
    public function will_reject_non_existant_password_reset_token()
    {
        $token = 'RESET_TOKEN_123';
        $id = '12345-1234-1234-1234-12345';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->getIdByPasswordResetToken($token)
            ->willThrow(new GoneException())
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(GoneException::class);
        $result = $us->canResetPassword($token);
    }

    /** @test */
    public function will_delete_a_user_account()
    {
        $id = '12345-1234-1234-1234-12345';

        $userData = [
            'Id'        => $id,
            'Email'     => 'a@b.com',
            'LastLogin' => null,
            'Password'  => self::PASS_HASH
        ];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->get($id)
            ->willReturn($userData)
            ->shouldBeCalled();

        $repoProphecy
            ->delete($id)
            ->willReturn($userData)
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $result = $us->deleteUserAccount($id);

        $this->assertEquals($userData, $result);
    }

    /** @test */
    public function can_request_email_reset()
    {
        $id = '12345-1234-1234-1234-12345';
        $email = 'a@b.com';
        $newEmail = 'new@email.com';
        $resetToken = 'abcde12345';
        $resetExpiry = time() + (60 * 60 * 48);

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->get($id)
            ->willReturn([
                'Id'        => $id,
                'Email'     => $email,
                'LastLogin' => null,
                'Password'  => self::PASS_HASH
            ])
            ->shouldBeCalled();

        $repoProphecy
            ->exists($newEmail)
            ->willReturn(false)
            ->shouldBeCalled();

        $repoProphecy
            ->getUserByNewEmail($newEmail)
            ->willReturn([])
            ->shouldBeCalled();

        $repoProphecy
            ->recordChangeEmailRequest($id, $newEmail, Argument::type('string'), Argument::type('int'))
            ->willReturn([
                'Id'               => $id,
                'EmailResetExpiry' => $resetExpiry,
                'Email'            => $email,
                'LastLogin'        => null,
                'NewEmail'         => $newEmail,
                'EmailResetToken'  => $resetToken,
                'Password'         => self::PASS_HASH
            ])->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $reset = $us->requestChangeEmail($id, $newEmail, new HiddenString(self::PASS));

        $this->assertEquals($id, $reset['Id']);
        $this->assertEquals($email, $reset['Email']);
        $this->assertEquals(self::PASS_HASH, $reset['Password']);
        $this->assertEquals($newEmail, $reset['NewEmail']);
        $this->assertEquals($resetToken, $reset['EmailResetToken']);
        $this->assertArrayHasKey('EmailResetExpiry', $reset);
    }

    /** @test */
    public function will_throw_exception_for_incorrect_password_in_request_email_reset()
    {
        $id = '12345-1234-1234-1234-12345';
        $newEmail = 'new@email.com';
        $password = 'inc0rr3ct';

        $userData = [
            'Id'        => $id,
            'Email'     => 'a@b.com',
            'LastLogin' => null,
            'Password'  => self::PASS_HASH
        ];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->get($id)
            ->willReturn($userData)
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(ForbiddenException::class);
        $us->requestChangeEmail($id, $newEmail, new HiddenString($password));
    }

    /** @test */
    public function will_throw_exception_if_new_email_is_taken_by_another_user()
    {
        $id = '12345-1234-1234-1234-12345';
        $newEmail = 'new@email.com';

        $userData = [
            'Id'        => $id,
            'Email'     => 'a@b.com',
            'LastLogin' => null,
            'Password'  => self::PASS_HASH
        ];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->get($id)
            ->willReturn($userData)
            ->shouldBeCalled();

        $repoProphecy
            ->exists($newEmail)
            ->willReturn(true)
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(ConflictException::class);
        $us->requestChangeEmail($id, $newEmail, new HiddenString(self::PASS));
    }

    /** @test */
    public function will_throw_exception_if_new_email_has_been_requested_for_reset_by_another_user()
    {
        $id = '12345-1234-1234-1234-12345';
        $newEmail = 'new@email.com';

        $userData = [
            'Id'        => $id,
            'Email'     => 'a@b.com',
            'LastLogin' => null,
            'Password'  => self::PASS_HASH
        ];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->get($id)
            ->willReturn($userData)
            ->shouldBeCalled();

        $repoProphecy
            ->exists($newEmail)
            ->willReturn(false)
            ->shouldBeCalled();

        $repoProphecy
            ->getUserByNewEmail($newEmail)
            ->willReturn([
                0 => [
                'EmailResetExpiry' => time() + (60 * 60 * 36),
                'Email'            => 'other@user.com',
                'LastLogin'        => null,
                'Id'               => '43210',
                'NewEmail'         => $newEmail,
                'EmailResetToken'  => 're3eT0ken',
                'Password'         => 'otherPa33w0rd',
                ]
            ])
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(ConflictException::class);
        $us->requestChangeEmail($id, $newEmail, new HiddenString(self::PASS));
    }

    /** @test */
    public function can_reset_email_function_throws_gone_exception_if_token_not_found_or_expired()
    {
        $token = 't0k3n12345';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->getIdByEmailResetToken($token)
            ->willThrow(new NotFoundException())
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(GoneException::class);
        $us->canResetEmail($token);
    }

    /** @test */
    public function complete_change_email_function_returns_nothing_when_successful()
    {
        $id = '12345-1234-1234-1234-12345';
        $token = 're3eT0ken';
        $newEmail = 'new@email.com';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->getIdByEmailResetToken($token)
            ->willReturn($id)
            ->shouldBeCalled();

        $repoProphecy
            ->get($id)
            ->willReturn([
                'EmailResetExpiry' => time() + (60 * 60 * 36),
                'Email'            => 'current@email.com',
                'LastLogin'        => null,
                'Id'               => $id,
                'NewEmail'         => $newEmail,
                'EmailResetToken'  => $token,
                'Password'         => self::PASS_HASH,
            ])
            ->shouldBeCalled();

        $repoProphecy
            ->changeEmail($id, $token, $newEmail)
            ->willReturn(true)
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $response = $us->completeChangeEmail($token);
        $this->assertNull($response);

    }

    /** @test */
    public function will_complete_change_password_given_a_valid_token_and_password()
    {
        $token = 'RESET_TOKEN_123';
        $password1 = self::PASS_HASH;
        $password2 = 'newpassword';
        $id = '12345-1234-1234-1234-12345';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy
            ->get($id)
            ->willReturn([
                    'Id'        => $id,
                    'Email'     => 'a@b.com',
                    'LastLogin' => null,
                    'Password'  => self::PASS_HASH
                ])
            ->shouldBeCalled();

        $repoProphecy
            ->resetPassword($id, $password2)
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $us->completeChangePassword($id, new HiddenString($password1), new HiddenString($password2));
    }
}
