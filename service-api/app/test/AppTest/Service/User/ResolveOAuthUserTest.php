<?php

declare(strict_types=1);

namespace AppTest\Service\User;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\ConflictException;
use App\Exception\CreationException;
use App\Exception\NotFoundException;
use App\Service\Log\EventCodes;
use App\Service\Log\Output\Email;
use App\Service\User\RecoverAccount;
use App\Service\User\ResolveOAuthUser;
use App\Service\User\UserService;
use Aws\CommandInterface;
use Aws\DynamoDb\Exception\DynamoDbException;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type ActorUser from ActorUsersInterface
 */
#[CoversClass(ResolveOAuthUser::class)]
class ResolveOAuthUserTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function linked_onelogin_user_returns_user(): void
    {
        $actorUsersInterfaceProphecy = $this->prophesize(ActorUsersInterface::class);
        $actorUsersInterfaceProphecy
            ->recordSuccessfulLogin('fakeId', Argument::cetera())
            ->shouldBeCalled();

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy
            ->getByIdentity('fakeSub')
            ->willReturn(
                [
                    'Id'        => 'fakeId',
                    'Identity'  => 'fakeSub',
                    'Email'     => 'fakeEmail',
                    'LastLogin' => (new DateTimeImmutable('-1 day'))->format(DateTimeInterface::ATOM),
                ]
            );

        $recoverAccountProphecy = $this->prophesize(RecoverAccount::class);

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $sut = new ResolveOAuthUser(
            $actorUsersInterfaceProphecy->reveal(),
            $userServiceProphecy->reveal(),
            $recoverAccountProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $user = ($sut)('fakeSub', 'fakeEmail');

        $this->assertArrayHasKey('Identity', $user);
        $this->assertEquals('fakeSub', $user['Identity']);
        $this->assertArrayHasKey('Email', $user);
        $this->assertEquals('fakeEmail', $user['Email']);
    }

    #[Test]
    public function linked_onelogin_user_updates_email_returns_user(): void
    {
        $actorUsersInterfaceProphecy = $this->prophesize(ActorUsersInterface::class);
        $actorUsersInterfaceProphecy
            ->recordSuccessfulLogin('fakeId', Argument::cetera())
            ->shouldBeCalled();
        $actorUsersInterfaceProphecy
            ->changeEmail('fakeId', 'fakeEmail', 'newFakeEmail')
            ->shouldBeCalled();

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy
            ->getByIdentity('fakeSub')
            ->willReturn(
                [
                    'Id'        => 'fakeId',
                    'Identity'  => 'fakeSub',
                    'Email'     => 'fakeEmail',
                    'LastLogin' => (new DateTimeImmutable('-1 day'))->format(DateTimeInterface::ATOM),
                ]
            );

        $recoverAccountProphecy = $this->prophesize(RecoverAccount::class);

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $sut = new ResolveOAuthUser(
            $actorUsersInterfaceProphecy->reveal(),
            $userServiceProphecy->reveal(),
            $recoverAccountProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $user = ($sut)('fakeSub', 'newFakeEmail');

        $this->assertArrayHasKey('Identity', $user);
        $this->assertEquals('fakeSub', $user['Identity']);
        $this->assertArrayHasKey('Email', $user);
        $this->assertEquals('newFakeEmail', $user['Email']);
    }

    #[Test]
    public function linked_onelogin_user_updates_email_recovers_user_account(): void
    {
        $actorUsersInterfaceProphecy = $this->prophesize(ActorUsersInterface::class);
        $actorUsersInterfaceProphecy
            ->recordSuccessfulLogin('fakeId', Argument::cetera())
            ->shouldBeCalled();
        $actorUsersInterfaceProphecy
            ->changeEmail(Argument::cetera())
            ->shouldNotBeCalled();

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy
            ->getByIdentity('fakeSub')
            ->willReturn(
                [
                    'Id'        => 'fakeId',
                    'Identity'  => 'fakeSub',
                    'Email'     => 'fakeEmail',
                    'LastLogin' => (new DateTimeImmutable('-1 day'))->format(DateTimeInterface::ATOM),
                ]
            );

        $recoverAccountProphecy = $this->prophesize(RecoverAccount::class);
        $recoverAccountProphecy
            ->__invoke(Argument::cetera())
            ->willReturn(
                [
                    'Id'        => 'fakeId',
                    'Identity'  => 'fakeSub',
                    'Email'     => 'newFakeEmail',
                    'LastLogin' => (new DateTimeImmutable('-1 day'))->format(DateTimeInterface::ATOM),
                ]
            );

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $sut = new ResolveOAuthUser(
            $actorUsersInterfaceProphecy->reveal(),
            $userServiceProphecy->reveal(),
            $recoverAccountProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $user = ($sut)('fakeSub', 'newFakeEmail');

        $this->assertArrayHasKey('Identity', $user);
        $this->assertEquals('fakeSub', $user['Identity']);
        $this->assertArrayHasKey('Email', $user);
        $this->assertEquals('newFakeEmail', $user['Email']);
    }

    #[Test]
    public function linked_onelogin_user_update_throws_exception_when_conflict(): void
    {
        $actorUsersInterfaceProphecy = $this->prophesize(ActorUsersInterface::class);
        $actorUsersInterfaceProphecy
            ->changeEmail(Argument::cetera())
            ->shouldNotBeCalled();

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy
            ->getByIdentity('fakeSub')
            ->willReturn(
                [
                    'Id'        => 'fakeId',
                    'Identity'  => 'fakeSub',
                    'Email'     => 'fakeEmail',
                    'LastLogin' => (new DateTimeImmutable('-1 day'))->format(DateTimeInterface::ATOM),
                ]
            );

        $recoverAccountProphecy = $this->prophesize(RecoverAccount::class);
        $recoverAccountProphecy
            ->__invoke(Argument::cetera())
            ->willThrow(new DynamoDbException(
                'message',
                $this->prophesize(CommandInterface::class)->reveal(),
                ['body' => ['CancellationReasons' => [['Code' => 'ConditionalCheckFailed']]]],
            ));

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $sut = new ResolveOAuthUser(
            $actorUsersInterfaceProphecy->reveal(),
            $userServiceProphecy->reveal(),
            $recoverAccountProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(ConflictException::class);
        ($sut)('fakeSub', 'newFakeEmail');
    }

    #[Test]
    public function linked_onelogin_user_update_throws_exception_when_other_dynamo_issue(): void
    {
        $actorUsersInterfaceProphecy = $this->prophesize(ActorUsersInterface::class);
        $actorUsersInterfaceProphecy
            ->changeEmail(Argument::cetera())
            ->shouldNotBeCalled();

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy
            ->getByIdentity('fakeSub')
            ->willReturn(
                [
                    'Id'        => 'fakeId',
                    'Identity'  => 'fakeSub',
                    'Email'     => 'fakeEmail',
                    'LastLogin' => (new DateTimeImmutable('-1 day'))->format(DateTimeInterface::ATOM),
                ]
            );

        $recoverAccountProphecy = $this->prophesize(RecoverAccount::class);
        $recoverAccountProphecy
            ->__invoke(Argument::cetera())
            ->willThrow(new DynamoDbException(
                'message',
                $this->prophesize(CommandInterface::class)->reveal(),
                ['body' => ['CancellationReasons' => [['Code' => 'None']]]],
            ));

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $sut = new ResolveOAuthUser(
            $actorUsersInterfaceProphecy->reveal(),
            $userServiceProphecy->reveal(),
            $recoverAccountProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(DynamoDbException::class);
        ($sut)('fakeSub', 'newFakeEmail');
    }

    #[Test]
    public function new_onelogin_user_returns_existing_user(): void
    {
        $actorUsersInterfaceProphecy = $this->prophesize(ActorUsersInterface::class);
        $actorUsersInterfaceProphecy
            ->migrateToOAuth(['Id' => 'fakeId'], 'fakeSub')
            ->willReturn(
                [
                    'Id'        => 'fakeId',
                    'Identity'  => 'fakeSub',
                    'Email'     => 'fakeEmail',
                    'LastLogin' => (new DateTimeImmutable('-1 day'))->format(DateTimeInterface::ATOM),
                ]
            );
        $actorUsersInterfaceProphecy
            ->recordSuccessfulLogin('fakeId', Argument::cetera())
            ->shouldBeCalled();

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy
            ->getByIdentity('fakeSub')
            ->willThrow(NotFoundException::class);
        $userServiceProphecy
            ->getByEmail('fakeEmail')
            ->willReturn(
                [
                    'Id' => 'fakeId',
                ]
            );

        $recoverAccountProphecy = $this->prophesize(RecoverAccount::class);

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $sut = new ResolveOAuthUser(
            $actorUsersInterfaceProphecy->reveal(),
            $userServiceProphecy->reveal(),
            $recoverAccountProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $user = ($sut)('fakeSub', 'fakeEmail');

        $this->assertArrayHasKey('Identity', $user);
        $this->assertEquals('fakeSub', $user['Identity']);
        $this->assertArrayHasKey('Email', $user);
        $this->assertEquals('fakeEmail', $user['Email']);
    }

    #[Test]
    public function known_email_has_unexpected_one_login_subject(): void
    {
        $actorUsersInterfaceProphecy = $this->prophesize(ActorUsersInterface::class);
        $actorUsersInterfaceProphecy
            ->recordSuccessfulLogin('fakeId', Argument::cetera())
            ->shouldBeCalled();

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy
            ->getByIdentity('fakeSub')
            ->willThrow(NotFoundException::class);
        $userServiceProphecy
            ->getByEmail('fakeEmail')
            ->willReturn(
                [
                    'Id'       => 'fakeId',
                    'Identity' => 'fakeSub',
                ]
            );
        $userServiceProphecy
            ->add('fakeEmail', 'fakeSub')
            ->willReturn(
                [
                    'Id'       => 'fakeId',
                    'Email'    => 'fakeEmail',
                    'Identity' => 'fakeSub',
                ]
            );

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $sut = new ResolveOAuthUser(
            $actorUsersInterfaceProphecy->reveal(),
            $userServiceProphecy->reveal(),
            $this->prophesize(RecoverAccount::class)->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $user = ($sut)('fakeSub', 'fakeEmail');

        $this->assertArrayHasKey('Identity', $user);
        $this->assertEquals('fakeSub', $user['Identity']);
        $this->assertArrayHasKey('Email', $user);
        $this->assertEquals('fakeEmail', $user['Email']);
    }

    #[Test]
    public function known_email_has_unexpected_one_login_subject_that_is_orphan(): void
    {
        $actorUsersInterfaceProphecy = $this->prophesize(ActorUsersInterface::class);
        $actorUsersInterfaceProphecy
            ->recordSuccessfulLogin('fakeId', Argument::cetera())
            ->shouldBeCalled();

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy
            ->getByIdentity('fakeSub')
            ->willThrow(NotFoundException::class);
        $userServiceProphecy
            ->getByEmail('fakeEmail')
            ->willReturn(
                [
                    'Id'       => 'fakeId',
                    'Identity' => 'fakeSub',
                ]
            );
        $userServiceProphecy
            ->add('fakeEmail', 'fakeSub')
            ->willThrow(new ConflictException('', ['identity' => 'fakeSub']));
        $userServiceProphecy
            ->addWithOrphanIdentityBypass('fakeEmail', 'fakeSub')
            ->willReturn(
                [
                    'Id'       => 'fakeId',
                    'Email'    => 'fakeEmail',
                    'Identity' => 'fakeSub',
                ]
            );

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $sut = new ResolveOAuthUser(
            $actorUsersInterfaceProphecy->reveal(),
            $userServiceProphecy->reveal(),
            $this->prophesize(RecoverAccount::class)->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $user = ($sut)('fakeSub', 'fakeEmail');

        $this->assertArrayHasKey('Identity', $user);
        $this->assertEquals('fakeSub', $user['Identity']);
        $this->assertArrayHasKey('Email', $user);
        $this->assertEquals('fakeEmail', $user['Email']);
    }

    #[Test]
    public function new_onelogin_user_returns_brand_new_user(): void
    {
        $actorUsersInterfaceProphecy = $this->prophesize(ActorUsersInterface::class);
        $actorUsersInterfaceProphecy
            ->recordSuccessfulLogin('fakeId', Argument::cetera())
            ->shouldBeCalled();

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy
            ->getByIdentity('fakeSub')
            ->willThrow(NotFoundException::class);
        $userServiceProphecy
            ->getByEmail('fakeEmail')
            ->willThrow(NotFoundException::class);
        $userServiceProphecy
            ->add('fakeEmail', 'fakeSub')
            ->willReturn(
                [
                    'Id'       => 'fakeId',
                    'Email'    => 'fakeEmail',
                    'Identity' => 'fakeSub',
                ]
            );

        $recoverAccountProphecy = $this->prophesize(RecoverAccount::class);

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $sut = new ResolveOAuthUser(
            $actorUsersInterfaceProphecy->reveal(),
            $userServiceProphecy->reveal(),
            $recoverAccountProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $user = ($sut)('fakeSub', 'fakeEmail');

        $this->assertArrayHasKey('Identity', $user);
        $this->assertEquals('fakeSub', $user['Identity'] ?? '');
        $this->assertArrayHasKey('Email', $user);
        $this->assertEquals('fakeEmail', $user['Email']);
    }

    #[Test]
    public function existing_user_when_conflict_on_adding(): void
    {
        $actorUsersInterfaceProphecy = $this->prophesize(ActorUsersInterface::class);

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy
            ->getByIdentity('fakeSub')
            ->willThrow(NotFoundException::class);
        $userServiceProphecy
            ->getByEmail('fakeEmail')
            ->willThrow(NotFoundException::class);
        $userServiceProphecy
            ->add('fakeEmail', 'fakeSub')
            ->willThrow(ConflictException::class);

        $recoverAccountProphecy = $this->prophesize(RecoverAccount::class);

        $clockProphecy = $this->prophesize(ClockInterface::class);
        $clockProphecy->now()->willReturn(new DateTimeImmutable('now'));

        $sut = new ResolveOAuthUser(
            $actorUsersInterfaceProphecy->reveal(),
            $userServiceProphecy->reveal(),
            $recoverAccountProphecy->reveal(),
            $clockProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(ConflictException::class);
        $user = ($sut)('fakeSub', 'fakeEmail');
    }

    #[Test]
    public function it_should_retry_an_orphaned_migration_without_creating_an_account(): void
    {
        $original = [
            'Id'    => 'existing-id',
            'Email' => 'user@example.com',
        ];
        $migrated = $original + ['Identity' => 'subject'];
        $now      = new DateTimeImmutable('2026-11-12T12:00:00+00:00');
        $attempt  = 0;

        $users = $this->createMock(UserService::class);
        $users->expects(self::once())
            ->method('getByIdentity')
            ->with('subject')
            ->willThrowException(new NotFoundException());
        $users->expects(self::once())->method('getByEmail')->with($original['Email'])->willReturn($original);
        $users->expects(self::never())->method('add');
        $users->expects(self::never())->method('addWithOrphanIdentityBypass');

        $repository = $this->createMock(ActorUsersInterface::class);
        $repository->expects(self::exactly(2))
            ->method('migrateToOAuth')
            ->willReturnCallback(function (
                array $user,
                string $identity,
                bool $ignoreOrphanIdentity = false,
            ) use (
                &$attempt,
                $original,
                $migrated
            ): array {
                self::assertSame($original, $user);
                self::assertSame('subject', $identity);
                self::assertSame($attempt === 1, $ignoreOrphanIdentity);
                $attempt++;

                if (! $ignoreOrphanIdentity) {
                    throw new ConflictException('', ['identity' => $identity]);
                }

                return $migrated;
            });
        $repository->expects(self::once())
            ->method('recordSuccessfulLogin')
            ->with($original['Id'], $now->format(DateTimeInterface::ATOM));

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn($now);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'Migrated existing account with email {email} to OIDC login with orphan bypass',
                [
                    'identity'   => 'subject',
                    'email'      => new Email($original['Email']),
                    'event_code' => EventCodes::AUTH_ONELOGIN_ACCOUNT_MIGRATED_WITH_ORPHAN_BYPASS,
                ],
            );

        $resolver = new ResolveOAuthUser(
            $repository,
            $users,
            $this->createStub(RecoverAccount::class),
            $clock,
            $logger,
        );

        self::assertSame($migrated, $resolver('subject', $original['Email']));
    }

    #[Test]
    #[DataProvider('migrationFailures')]
    public function it_should_propagate_migration_failures_without_bypass(
        CreationException|ConflictException $error,
    ): void {
        $original = [
            'Id'    => 'existing-id',
            'Email' => 'user@example.com',
        ];

        $users = $this->createMock(UserService::class);
        $users->method('getByIdentity')->willThrowException(new NotFoundException());
        $users->method('getByEmail')->willReturn($original);
        $users->expects(self::never())->method('add');
        $users->expects(self::never())->method('addWithOrphanIdentityBypass');

        $repository = $this->createMock(ActorUsersInterface::class);
        $repository->expects(self::once())
            ->method('migrateToOAuth')
            ->with($original, 'subject')
            ->willThrowException($error);
        $repository->expects(self::never())->method('recordSuccessfulLogin');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('info');

        $resolver = new ResolveOAuthUser(
            $repository,
            $users,
            $this->createStub(RecoverAccount::class),
            $this->createStub(ClockInterface::class),
            $logger,
        );

        $this->expectExceptionObject($error);

        $resolver('subject', $original['Email']);
    }

    public static function migrationFailures(): array
    {
        return [
            'unrelated conflict'       => [new ConflictException()],
            'different identity'       => [new ConflictException('', ['identity' => 'another-subject'])],
            'additional conflict data' => [new ConflictException('', ['identity' => 'subject', 'other' => 'conflict'])],
            'transaction failure'      => [new CreationException()],
        ];
    }

    #[Test]
    public function it_should_propagate_a_failed_bypass_without_recording_a_successful_login(): void
    {
        $original = [
            'Id'    => 'existing-id',
            'Email' => 'user@example.com',
        ];
        $error    = new CreationException('Migration failed');

        $users = $this->createMock(UserService::class);
        $users->method('getByIdentity')->willThrowException(new NotFoundException());
        $users->method('getByEmail')->willReturn($original);
        $users->expects(self::never())->method('add');

        $repository = $this->createMock(ActorUsersInterface::class);
        $repository->expects(self::exactly(2))
            ->method('migrateToOAuth')
            ->willReturnCallback(function (
                array $user,
                string $identity,
                bool $ignoreOrphanIdentity = false,
            ) use ($error): array {
                if ($ignoreOrphanIdentity) {
                    throw $error;
                }

                throw new ConflictException('', ['identity' => $identity]);
            });
        $repository->expects(self::never())->method('recordSuccessfulLogin');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('info');

        $resolver = new ResolveOAuthUser(
            $repository,
            $users,
            $this->createStub(RecoverAccount::class),
            $this->createStub(ClockInterface::class),
            $logger,
        );

        $this->expectExceptionObject($error);

        $resolver('subject', $original['Email']);
    }
}
