<?php

declare(strict_types=1);

namespace AppTest\Service\User;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Service\User\UserService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class UserServiceTest extends TestCase
{
    use ProphecyTrait;

    private const string PASS_HASH = '$2y$13$s2xLSYAO3iM020NB07KkReTTn5r/E6ReJiY/UO8WOA9b7udINcgia';

    private string $id;

    #[Test]
    public function can_add_a_new_user(): void
    {
        $email    = 'a@b.com';
        $identity = 'urn:fdc:one-login:2023:HASH=';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->exists($email)->willReturn(false);
        $repoProphecy->add(
            Argument::that(function (string $data): bool {
                $this->id = $data;
                return Uuid::isValid($data);
            }),
            $email,
            $identity,
        );

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $return = $us->add($email, $identity);

        $this->assertSame(
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
        $email    = 'a@b.com';
        $identity = 'urn:fdc:one-login:2023:HASH=';

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->exists($email)->willReturn(true);

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );

        $this->expectException(ConflictException::class);
        $us->add($email, $identity);
    }

    #[Test]
    public function can_get_a_user_from_storage(): void
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);
        $repoProphecy->getByEmail('a@b.com')
            ->willReturn(['Email' => 'a@b.com', 'Password' => self::PASS_HASH]);

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $return = $us->getByEmail('a@b.com');

        $this->assertSame(['Email' => 'a@b.com', 'Password' => self::PASS_HASH], $return);
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
                    'Password' => self::PASS_HASH,
                ]
            );

        $us = new UserService(
            $repoProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $return = $us->getByIdentity('urn:fdc:one-login:2023:HASH=');

        $this->assertSame(
            [
                'Email'    => 'a@b.com',
                'Identity' => 'urn:fdc:one-login:2023:HASH=',
                'Password' => self::PASS_HASH,
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
            $this->prophesize(LoggerInterface::class)->reveal(),
        );

        $result = $us->deleteUserAccount($id);

        $this->assertEquals($userData, $result);
    }
}
