<?php

declare(strict_types=1);

namespace AppTest\Service\User;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\ConflictException;
use App\Exception\CreationException;
use App\Exception\NotFoundException;
use App\Service\User\UserService;
use Aws\CommandInterface;
use Aws\DynamoDb\Exception\DynamoDbException;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class UserServiceTest extends TestCase
{
    use ProphecyTrait;

    private string $id;

    #[Test]
    public function can_add_a_new_user(): void
    {
        $email    = 'a@b.com';
        $identity = 'urn:fdc:one-login:2023:HASH=';
        $now      = new DateTimeImmutable();

        /** @var ObjectProphecy<ActorUsersInterface> $repoProphecy */
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->add(
            Argument::that(function (string $data) {
                $this->id = $data;
                return Uuid::isValid($data);
            }),
            $email,
            $identity,
            $now->format(DateTimeInterface::ATOM),
            false,
        );

        $clock = $this->prophesize(ClockInterface::class);
        $clock->now()->willReturn($now);

        $us = new UserService(
            $repoProphecy->reveal(),
            $clock->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $return = $us->add($email, $identity);

        $this->assertEquals(
            [
                'Id'       => $this->id,
                'Email'    => $email,
                'Identity' => $identity,
            ],
            $return
        );
    }

    #[Test]
    public function can_add_a_new_user_with_ignored_orphan_identity(): void
    {
        $email    = 'a@b.com';
        $identity = 'urn:fdc:one-login:2023:HASH=';
        $now      = new DateTimeImmutable();

        /** @var ObjectProphecy<ActorUsersInterface> $repoProphecy */
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->add(
            Argument::that(function (string $data) {
                $this->id = $data;
                return Uuid::isValid($data);
            }),
            $email,
            $identity,
            $now->format(DateTimeInterface::ATOM),
            true,
        );

        $clock = $this->prophesize(ClockInterface::class);
        $clock->now()->willReturn($now);

        $us = new UserService(
            $repoProphecy->reveal(),
            $clock->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $return = $us->addWithOrphanIdentityBypass($email, $identity);

        $this->assertEquals(
            [
                'Id'       => $this->id,
                'Email'    => $email,
                'Identity' => $identity,
            ],
            $return
        );
    }

    #[Test]
    public function wont_add_a_user_that_already_exists(): void
    {
        $command = $this->prophesize(CommandInterface::class);

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->add(Argument::cetera())
            ->willThrow(ConflictException::class);

        $clock = $this->prophesize(ClockInterface::class);
        $clock->now()->willReturn(new DateTimeImmutable());

        $us = new UserService(
            $repoProphecy->reveal(),
            $clock->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $this->expectException(ConflictException::class);
        $us->add('test@example.com', 'identity');
    }

    #[Test]
    public function wont_add_a_user_that_already_exists_with_ignored_orphan_identity(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->add(Argument::cetera())
            ->willThrow(CreationException::class);

        $clock = $this->prophesize(ClockInterface::class);
        $clock->now()->willReturn(new DateTimeImmutable());

        $us = new UserService(
            $repoProphecy->reveal(),
            $clock->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $this->expectException(CreationException::class);
        $us->addWithOrphanIdentityBypass('test@example.com', 'identity');
    }

    #[Test]
    public function add_throws_other_dynamo_errors(): void
    {
        $command = $this->prophesize(CommandInterface::class);

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->add(Argument::cetera())
            ->willThrow(new DynamoDbException('', $command->reveal(), []));

        $clock = $this->prophesize(ClockInterface::class);
        $clock->now()->willReturn(new DateTimeImmutable());

        $us = new UserService(
            $repoProphecy->reveal(),
            $clock->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $this->expectException(DynamoDbException::class);
        $us->add('', '');
    }

    #[Test]
    public function can_get_a_user_from_storage(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByEmail('a@b.com')
            ->willReturn(['Email' => 'a@b.com']);

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $return = $us->getByEmail('a@b.com');

        $this->assertEquals(['Email' => 'a@b.com'], $return);
    }

    #[Test]
    public function can_get_a_user_from_storage_using_identity(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByIdentity('urn:fdc:one-login:2023:HASH=')
            ->willReturn(
                [
                    'Email'    => 'a@b.com',
                    'Identity' => 'urn:fdc:one-login:2023:HASH=',
                ]
            );

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $return = $us->getByIdentity('urn:fdc:one-login:2023:HASH=');

        $this->assertEquals(
            [
                'Email'    => 'a@b.com',
                'Identity' => 'urn:fdc:one-login:2023:HASH=',
            ],
            $return,
        );
    }

    #[Test]
    public function cannot_retrieve_a_user_that_doesnt_exist(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByEmail('a@b.com')
            ->willThrow(new NotFoundException());

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(NotFoundException::class);
        $us->getByEmail('a@b.com');
    }

    #[Test]
    public function cannot_retrieve_a_user_that_doesnt_exist_using_identity(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByIdentity('urn:fdc:one-login:2023:HASH=')
            ->willThrow(new NotFoundException());

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(ClockInterface::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $this->expectException(NotFoundException::class);
        $us->getByIdentity('urn:fdc:one-login:2023:HASH=');
    }

    #[Test]
    public function will_delete_a_user_account(): void
    {
        $id = '12345-1234-1234-1234-12345';

        $userData = [
            'Id'        => $id,
            'Email'     => 'a@b.com',
            'LastLogin' => null,
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
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $result = $us->deleteUserAccount($id);

        $this->assertEquals($userData, $result);
    }
}
