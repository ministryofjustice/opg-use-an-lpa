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
use App\Service\Log\Output\Email;
use App\Service\RandomByteGenerator;
use App\Service\User\UserService;
use DateTime;
use DateTimeImmutable;
use Exception;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class UserServiceTest extends TestCase
{
    use ProphecyTrait;

    // Password hash for password 'test' generated using PASSWORD_DEFAULT
    private const PASS = 'test';

    private const INSECURE_PASS_HASH = '$2y$10$Ew4y5jzm6fGKAB16huUw6ugZbuhgW5cvBQ6DGVDFzuyBXsCw51dzq';
    private const PASS_HASH          = '$2y$13$s2xLSYAO3iM020NB07KkReTTn5r/E6ReJiY/UO8WOA9b7udINcgia';

    private int $expiresTTL;

    private string $id;
    private string $activationToken;

    /** @test */
    public function can_create_a_valid_instance(): void
    {
        $us = new UserService(
            $this->prophesize(ActorUsersInterface::class)->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->assertInstanceOf(UserService::class, $us);
    }

    /** @test */
    public function can_add_a_new_user(): void
    {
        $email    = 'a@b.com';
        $password = 'password1';

        $repoProphecy   = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->exists($email)->willReturn(false);
        $repoProphecy
            ->getUserByNewEmail($email)
            ->willReturn([])
            ->shouldBeCalled();
        $repoProphecy
            ->add(
                Argument::that(function (string $data) {
                    $this->id = $data;
                    return Uuid::isValid($data);
                }),
                $email,
                $password,
                Argument::that(function (string $activationToken) {
                    $this->activationToken = $activationToken;
                    return true;
                }),
                Argument::that(function (int $expiresTTL) {
                    $this->expiresTTL = $expiresTTL;
                    return true;
                })
            );

        $randomByteGenerator = $this->prophesize(RandomByteGenerator::class);
        $randomByteGenerator
            ->__invoke(Argument::any())
            ->willReturn('bigRandomBytes');

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $randomByteGenerator->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $return = $us->add(['email' => $email, 'password' => new HiddenString($password)]);

        $this->assertEquals(
            [
                'Id'              => $this->id,
                'ActivationToken' => $this->activationToken,
                'ExpiresTTL'      => $this->expiresTTL,
            ],
            $return
        );
    }

    /** @test */
    public function will_reset_existing_inactive_user_when_adding_again(): void
    {
        $id              = '12345678-1234-1234-1234-123456789012';
        $email           = 'a@b.com';
        $password        = 'password1';
        $activationToken = 'activationToken1';
        $ttl             = (new DateTime('+24 hours'))->getTimestamp();
        $userData        = ['email' => $email, 'password' => new HiddenString($password), 'id' => $id];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->exists($userData['email'])
            ->willReturn(true);
        $repoProphecy->getByEmail($userData['email'])
            ->willReturn([
                'Id'              => $id,
                'Email'           => $email,
                'ExpiresTTL'      => $ttl,
                'ActivationToken' => $activationToken,
            ]);
        $repoProphecy->resetActivationDetails($id, $password, Argument::type('integer'))
            ->willReturn([
                'Id'    => $id,
                'Email' => $email,
            ]);

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $return = $us->add($userData);

        $this->assertEquals(['Id' => $id, 'Email' => $email], $return);
    }

    /** @test
     * @throws Exception
     */
    public function cannot_add_existing_user_already_activated()
    {
        $id       = '12345678-1234-1234-1234-123456789012';
        $email    = 'a@b.com';
        $password = 'password1';
        $userData = ['email' => 'a@b.com', 'password' => $password];

        $repoProphecy   = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->exists($userData['email'])
            ->willReturn(true);
        $repoProphecy->getByEmail($userData['email'])
            ->willReturn([
                'Id'    => $id,
                'Email' => $email,
            ]);

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(ConflictException::class);
        $us->add($userData);
    }

    /** @test
     * @throws Exception
     */
    public function cannot_add_existing_user_as_email_used_in_reset()
    {
        $id       = '12345678-1234-1234-1234-123456789012';
        $email    = 'a@b.com';
        $password = new HiddenString('password1');
        $userData = ['email' => 'a@b.com', 'password' => $password];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->exists($userData['email'])
            ->willReturn(false);
        $repoProphecy->getUserByNewEmail($userData['email'])
            ->willReturn(
                [
                    [
                        'Id'               => $id,
                        'Email'            => $email,
                        'EmailResetExpiry' => '' . time() + (60 * 60 * 16), // expires in 16 hours
                    ],
                ]
            );

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $us = new UserService(
            $repoProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(ConflictException::class);
        $us->add($userData);
    }

    /** @test */
    public function logs_Notice_When_Password_Reset_Is_Requested_For_Non_Existent_Account(): void
    {
        $email        = 'nonexistent@example.com';
        $hashed_email = hash('sha256', $email);

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy
            ->recordPasswordResetRequest(Argument::cetera())
            ->willThrow(NotFoundException::class);

        $randomByteGeneratorProphecy = $this->prophesize(RandomByteGenerator::class);
        $randomByteGeneratorProphecy
            ->__invoke(Argument::any())
            ->willReturn('randomBytes');

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->notice(
                'Attempt made to reset password for non-existent account',
                Argument::that(function ($arg) use ($hashed_email) {
                    return $arg['email'] instanceof Email && (string)($arg['email']) === $hashed_email;
                })
            )
            ->shouldBeCalled();

        $userService = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $randomByteGeneratorProphecy->reveal(),
            $loggerProphecy->reveal(),
        );

        try {
            $userService->requestPasswordReset($email);
        } catch (Exception) {
        }
    }

    /** @test */
    public function can_get_a_user_from_storage(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByEmail('a@b.com')
            ->willReturn(['Email' => 'a@b.com', 'Password' => self::PASS_HASH]);

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $return = $us->getByEmail('a@b.com');

        $this->assertEquals(['Email' => 'a@b.com', 'Password' => self::PASS_HASH], $return);
    }

    /** @test */
    public function cannot_retrieve_a_user_that_doesnt_exist(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByEmail('a@b.com')
            ->willThrow(new NotFoundException());

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(NotFoundException::class);
        $return = $us->getByEmail('a@b.com');
    }

    public function can_get_a_user_from_storage_using_identity(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByIdentity('urn:fdc:one-login:2023:HASH=')
            ->willReturn(
                [
                    'Email'    => 'a@b.com',
                    'Identity' => 'urn:fdc:one-login:2023:HASH=',
                    'Password' => self::PASS_HASH,
                ]
            );

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $return = $us->getByIdentity('urn:fdc:one-login:2023:HASH=');

        $this->assertEquals(
            [
                'Email'    => 'a@b.com',
                'Identity' => 'urn:fdc:one-login:2023:HASH=',
                'Password' => self::PASS_HASH,
            ],
            $return,
        );
    }

    /** @test */
    public function cannot_retrieve_a_user_that_doesnt_exist_using_identity(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByIdentity('urn:fdc:one-login:2023:HASH=')
            ->willThrow(new NotFoundException());

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(NotFoundException::class);
        $return = $us->getByIdentity('urn:fdc:one-login:2023:HASH=');
    }

    /** @test */
    public function can_authenticate_a_user_with_valid_credentials(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByEmail('a@b.com')
            ->willReturn(
                [
                    'Id'        => '1234-1234-1234',
                    'Email'     => 'a@b.com',
                    'Password'  => self::PASS_HASH,
                    'LastLogin' => '2020-01-01',
                ]
            );
        $repoProphecy->recordSuccessfulLogin('1234-1234-1234', Argument::that(function ($dateTime) {
            $this->assertIsString($dateTime);

            $date = new DateTime($dateTime);
            $this->assertInstanceOf(DateTime::class, $date);

            return true;
        }));
        $repoProphecy->rehashPassword(Argument::cetera())
            ->shouldNotBeCalled();

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $us = new UserService(
            $repoProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $return = $us->authenticate('a@b.com', new HiddenString(self::PASS));

        $this->assertEquals(
            [
                'Id'        => '1234-1234-1234',
                'Email'     => 'a@b.com',
                'LastLogin' => '2020-01-01',
            ],
            $return
        );
    }

    /** @test */
    public function will_update_the_password_hash_on_successful_authentication(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByEmail('a@b.com')
            ->willReturn(
                [
                    'Id'        => '1234-1234-1234',
                    'Email'     => 'a@b.com',
                    'Password'  => self::INSECURE_PASS_HASH,
                    'LastLogin' => '2020-01-01',
                ]
            );
        $repoProphecy->rehashPassword('1234-1234-1234', Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);
        $repoProphecy->recordSuccessfulLogin('1234-1234-1234', Argument::any())
            ->shouldBeCalled();

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $us = new UserService(
            $repoProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $return = $us->authenticate('a@b.com', new HiddenString(self::PASS));

        $this->assertEquals(
            [
                'Id'        => '1234-1234-1234',
                'Email'     => 'a@b.com',
                'LastLogin' => '2020-01-01',
            ],
            $return
        );
    }

    /** @test */
    public function will_not_authenticate_invalid_credentials(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByEmail('a@b.com')
            ->willReturn(['Email' => 'a@b.com', 'Password' => self::PASS_HASH]);

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(ForbiddenException::class);
        $return = $us->authenticate('a@b.com', new HiddenString('badpassword'));
    }

    /** @test */
    public function will_not_authenticate_unknown_user(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByEmail('baduser@b.com')
            ->willThrow(new NotFoundException());

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(NotFoundException::class);
        $return = $us->authenticate('baduser@b.com', new HiddenString(self::PASS));
    }

    /** @test */
    public function will_not_authenticate_unverified_account(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByEmail('a@b.com')
            ->willReturn(
                [
                    'Email'           => 'a@b.com',
                    'Password'        => self::PASS_HASH,
                    'ActivationToken' => 'aToken',
                    'Id'              => '1234-1234-1234',
                ]
            );
        $repoProphecy->rehashPassword(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(UnauthorizedException::class);
        $return = $us->authenticate('a@b.com', new HiddenString(self::PASS));
    }

    /** @test */
    public function will_generate_and_record_a_password_reset_token(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy
            ->recordPasswordResetRequest('a@b.com', Argument::cetera())
            ->willReturn([
                'Email'              => 'a@b.com',
                'PasswordResetToken' => 'resetTokenAABBCCDDEE',
                'Id'                 => '1234-1234-1234',
            ]);

        $randomByteGeneratorProphecy = $this->prophesize(RandomByteGenerator::class);
        $randomByteGeneratorProphecy
            ->__invoke(Argument::any())
            ->willReturn('randomBytes');

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $randomByteGeneratorProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $return = $us->requestPasswordReset('a@b.com');

        $this->assertIsArray($return);
        $this->assertArrayHasKey('PasswordResetToken', $return);
        $this->assertIsString($return['PasswordResetToken']);
    }

    /** @test */
    public function will_reset_a_password_given_a_valid_token(): void
    {
        $token    = 'RESET_TOKEN_123';
        $password = 'newpassword';
        $id       = '12345-1234-1234-1234-12345';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy
            ->getIdByPasswordResetToken($token)
            ->willReturn($id)
            ->shouldBeCalled();
        $repoProphecy
            ->get($id)
            ->willReturn([
                'Id'                  => $id,
                'PasswordResetToken'  => $token,
                'PasswordResetExpiry' => (new DateTime('+1 week'))->format('U'),
            ])
            ->shouldBeCalled();
        $repoProphecy
            ->resetPassword($id, $password)
            ->shouldBeCalled();

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $us = new UserService(
            $repoProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $us->completePasswordReset($token, new HiddenString($password));
    }

    /** @test */
    public function will_not_reset_password_with_expired_token(): void
    {
        $token    = 'RESET_TOKEN_123';
        $password = 'newpassword';
        $id       = '12345-1234-1234-1234-12345';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy
            ->getIdByPasswordResetToken($token)
            ->willReturn($id)
            ->shouldBeCalled();
        $repoProphecy
            ->get($id)
            ->willReturn([
                'Id'                  => $id,
                'PasswordResetToken'  => $token,
                'PasswordResetExpiry' => (new DateTime('-1 week'))->format('U'),
            ])
            ->shouldBeCalled();

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $us = new UserService(
            $repoProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(BadRequestException::class);
        $us->completePasswordReset($token, new HiddenString($password));
    }

    /** @test */
    public function will_confirm_valid_password_reset_token(): void
    {
        $token = 'RESET_TOKEN_123';
        $id    = '12345-1234-1234-1234-12345';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy
            ->getIdByPasswordResetToken($token)
            ->willReturn($id)
            ->shouldBeCalled();
        $repoProphecy
            ->get($id)
            ->willReturn([
                'Id'                  => $id,
                'PasswordResetToken'  => $token,
                'PasswordResetExpiry' => (new DateTime('+1 week'))->format('U'),
            ])
            ->shouldBeCalled();

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $us = new UserService(
            $repoProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $result = $us->canResetPassword($token);

        $this->assertEquals($id, $result);
    }

    /** @test */
    public function will_reject_expired_password_reset_token(): void
    {
        $token = 'RESET_TOKEN_123';
        $id    = '12345-1234-1234-1234-12345';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy
            ->getIdByPasswordResetToken($token)
            ->willReturn($id)
            ->shouldBeCalled();
        $repoProphecy
            ->get($id)
            ->willReturn([
                'Id'                  => $id,
                'PasswordResetToken'  => $token,
                'PasswordResetExpiry' => (new DateTime('-1 week'))->format('U'),
            ])
            ->shouldBeCalled();

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $us = new UserService(
            $repoProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(GoneException::class);
        $result = $us->canResetPassword($token);
    }

    /** @test */
    public function will_reject_non_existent_password_reset_token(): void
    {
        $token = 'RESET_TOKEN_123';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy
            ->getIdByPasswordResetToken($token)
            ->willThrow(new GoneException())
            ->shouldBeCalled();

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(GoneException::class);
        $result = $us->canResetPassword($token);
    }

    /** @test */
    public function will_delete_a_user_account(): void
    {
        $id = '12345-1234-1234-1234-12345';

        $userData = [
            'Id'        => $id,
            'Email'     => 'a@b.com',
            'LastLogin' => null,
            'Password'  => self::PASS_HASH,
        ];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy
            ->get($id)
            ->willReturn($userData)
            ->shouldBeCalled();
        $repoProphecy
            ->delete($id)
            ->willReturn($userData)
            ->shouldBeCalled();

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $result = $us->deleteUserAccount($id);

        $this->assertEquals($userData, $result);
    }

    /** @test */
    public function can_request_email_reset(): void
    {
        $id          = '12345-1234-1234-1234-12345';
        $email       = 'a@b.com';
        $newEmail    = 'new@email.com';
        $resetToken  = 'abcde12345';
        $resetExpiry = time() + (60 * 60 * 48);

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy
            ->get($id)
            ->willReturn([
                'Id'        => $id,
                'Email'     => $email,
                'LastLogin' => null,
                'Password'  => self::PASS_HASH,
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
            ->recordChangeEmailRequest($id, $newEmail, Argument::cetera())
            ->willReturn([
                'Id'               => $id,
                'EmailResetExpiry' => $resetExpiry,
                'Email'            => $email,
                'LastLogin'        => null,
                'NewEmail'         => $newEmail,
                'EmailResetToken'  => $resetToken,
                'Password'         => self::PASS_HASH,
            ])->shouldBeCalled();

        $randomByteGeneratorProphecy = $this->prophesize(RandomByteGenerator::class);
        $randomByteGeneratorProphecy
            ->__invoke(Argument::any())
            ->willReturn('randomBytes');

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $randomByteGeneratorProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $reset = $us->requestChangeEmail($id, $newEmail, new HiddenString(self::PASS));

        $this->assertEquals($id, $reset['Id']);
        $this->assertEquals($email, $reset['Email']);
        $this->assertEquals(self::PASS_HASH, $reset['Password']);
        $this->assertEquals($newEmail, $reset['NewEmail']);
        $this->assertEquals($resetToken, $reset['EmailResetToken']);
        $this->assertArrayHasKey('EmailResetExpiry', $reset);
    }

    /** @test */
    public function will_throw_exception_for_incorrect_password_in_request_email_reset(): void
    {
        $id       = '12345-1234-1234-1234-12345';
        $newEmail = 'new@email.com';
        $password = 'inc0rr3ct';

        $userData = [
            'Id'        => $id,
            'Email'     => 'a@b.com',
            'LastLogin' => null,
            'Password'  => self::PASS_HASH,
        ];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy
            ->get($id)
            ->willReturn($userData)
            ->shouldBeCalled();

        $randomByteGeneratorProphecy = $this->prophesize(RandomByteGenerator::class);
        $randomByteGeneratorProphecy
            ->__invoke(Argument::any())
            ->willReturn('randomBytes');

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $randomByteGeneratorProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(ForbiddenException::class);
        $us->requestChangeEmail($id, $newEmail, new HiddenString($password));
    }

    /** @test */
    public function will_throw_exception_if_new_email_is_taken_by_another_user(): void
    {
        $id       = '12345-1234-1234-1234-12345';
        $newEmail = 'new@email.com';

        $userData = [
            'Id'        => $id,
            'Email'     => 'a@b.com',
            'LastLogin' => null,
            'Password'  => self::PASS_HASH,
        ];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy
            ->get($id)
            ->willReturn($userData)
            ->shouldBeCalled();
        $repoProphecy
            ->exists($newEmail)
            ->willReturn(true)
            ->shouldBeCalled();

        $randomByteGeneratorProphecy = $this->prophesize(RandomByteGenerator::class);
        $randomByteGeneratorProphecy
            ->__invoke(Argument::any())
            ->willReturn('randomBytes');

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $randomByteGeneratorProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(ConflictException::class);
        $us->requestChangeEmail($id, $newEmail, new HiddenString(self::PASS));
    }

    /** @test */
    public function will_throw_exception_if_new_email_has_been_requested_for_reset_by_another_user(): void
    {
        $id       = '12345-1234-1234-1234-12345';
        $newEmail = 'new@email.com';

        $userData = [
            'Id'        => $id,
            'Email'     => 'a@b.com',
            'LastLogin' => null,
            'Password'  => self::PASS_HASH,
        ];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
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
                ],
            ])
            ->shouldBeCalled();

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $randomByteGeneratorProphecy = $this->prophesize(RandomByteGenerator::class);
        $randomByteGeneratorProphecy
            ->__invoke(Argument::any())
            ->willReturn('randomBytes');

        $us = new UserService(
            $repoProphecy->reveal(),
            $clockProphecy->reveal(),
            $randomByteGeneratorProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(ConflictException::class);
        $us->requestChangeEmail($id, $newEmail, new HiddenString(self::PASS));
    }

    /** @test */
    public function can_reset_email_function_throws_gone_exception_if_token_not_found_or_expired(): void
    {
        $token = 't0k3n12345';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy
            ->getIdByEmailResetToken($token)
            ->willThrow(new NotFoundException())
            ->shouldBeCalled();

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(GoneException::class);
        $us->canResetEmail($token);
    }

    /** @test */
    public function complete_change_email_function_returns_nothing_when_successful(): void
    {
        $id       = '12345-1234-1234-1234-12345';
        $token    = 're3eT0ken';
        $newEmail = 'new@email.com';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
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

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(RandomByteGenerator::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $us->completeChangeEmail($token);
    }
}
