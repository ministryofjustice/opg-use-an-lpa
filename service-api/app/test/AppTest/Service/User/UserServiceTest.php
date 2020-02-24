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
            ->add(
                Argument::that(function(string $data) {
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
    public function cannot_add_existing_user()
    {
        $userData = ['email' => 'a@b.com', 'password' => self::PASS];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $repoProphecy->exists($userData['email'])
            ->willReturn(true);

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(ConflictException::class);
        $return = $us->add($userData);
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
            ->willReturn(['Id' => '1234-1234-1234', 'Email' => 'a@b.com', 'Password' => self::PASS_HASH]);
        $repoProphecy->recordSuccessfulLogin('1234-1234-1234', Argument::that(function($dateTime) {
            $this->assertIsString($dateTime);

            $date = new \DateTime($dateTime);
            $this->assertInstanceOf(\DateTime::class, $date);

            return true;
        }));

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $return = $us->authenticate('a@b.com', self::PASS);

        $this->assertEquals(['Id' => '1234-1234-1234', 'Email' => 'a@b.com', 'Password' => self::PASS_HASH], $return);
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
            ->willReturn(['Email' => 'a@b.com', 'Password' => self::PASS_HASH, 'ActivationToken' => 'aToken']);

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
                'PasswordResetToken' => 'resetTokenAABBCCDDEE'
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
                'PasswordResetExpiry' => (new \DateTime('+1 week'))->format('U')
            ])
            ->shouldBeCalled();

        $repoProphecy
            ->resetPassword($id, $password)
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $us->completePasswordReset($token, $password);
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
                'PasswordResetExpiry' => (new \DateTime('-1 week'))->format('U')
            ])
            ->shouldBeCalled();

        $us = new UserService($repoProphecy->reveal(), $loggerProphecy->reveal());

        $this->expectException(BadRequestException::class);
        $us->completePasswordReset($token, $password);
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
                'PasswordResetExpiry' => (new \DateTime('+1 week'))->format('U')
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
                'PasswordResetExpiry' => (new \DateTime('-1 week'))->format('U')
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


}