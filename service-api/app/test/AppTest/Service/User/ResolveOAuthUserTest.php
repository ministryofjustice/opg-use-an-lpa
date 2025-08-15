<?php

declare(strict_types=1);

namespace AppTest\Service\User;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\NotFoundException;
use App\Service\User\RecoverAccount;
use App\Service\User\ResolveOAuthUser;
use App\Service\User\UserService;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type ActorUser from ActorUsersInterface
 */
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
                    'Password'  => 'fakePassword',
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

        $this->assertArrayNotHasKey('Password', $user);
    }

    #[Test]
    public function linked_onelogin_user_updates_email_returns_user(): void
    {
        $actorUsersInterfaceProphecy = $this->prophesize(ActorUsersInterface::class);
        $actorUsersInterfaceProphecy
            ->recordSuccessfulLogin('fakeId', Argument::cetera())
            ->shouldBeCalled();
        $actorUsersInterfaceProphecy
            ->changeEmail('fakeId', 'newFakeEmail')
            ->shouldBeCalled();

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy
            ->getByIdentity('fakeSub')
            ->willReturn(
                [
                    'Id'        => 'fakeId',
                    'Identity'  => 'fakeSub',
                    'Email'     => 'fakeEmail',
                    'Password'  => 'fakePassword',
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

        $this->assertArrayNotHasKey('Password', $user);
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
                    'Password'  => 'fakePassword',
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
                    'Password'  => 'fakePassword',
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

        $this->assertArrayNotHasKey('Password', $user);
    }

    #[Test]
    public function new_onelogin_user_returns_existing_user(): void
    {
        $actorUsersInterfaceProphecy = $this->prophesize(ActorUsersInterface::class);
        $actorUsersInterfaceProphecy
            ->migrateToOAuth('fakeId', 'fakeSub')
            ->willReturn(
                [
                    'Id'        => 'fakeId',
                    'Identity'  => 'fakeSub',
                    'Email'     => 'fakeEmail',
                    'Password'  => 'fakePassword',
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

        $this->assertArrayNotHasKey('Password', $user);
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

        $this->assertArrayNotHasKey('Password', $user);
    }
}
