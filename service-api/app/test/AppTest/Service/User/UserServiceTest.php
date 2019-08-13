<?php

declare(strict_types=1);

namespace AppTest\Service\User;

use App\DataAccess\Repository\ActorUsersInterface;
use App\Exception\ConflictException;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\UnauthorizedException;
use App\Service\User\UserService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

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

        $us = new UserService($repoProphecy->reveal());

        $this->assertInstanceOf(UserService::class, $us);
    }

    /** @test */
    public function can_add_a_new_user()
    {
        $userData = [
            'email' => 'a@b.com',
            'password' => self::PASS,
            'activation_token' => '4O09oMEd57ZzF-xgEv7XMiGRLD7D8I0G9VawMykYVZU=',
            'ttl' => time() + 60
        ];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);

        $repoProphecy->exists($userData['email'])
            ->willReturn(false);
        $repoProphecy->add($userData['email'], $userData['password'], Argument::type('string'), Argument::type('int'))
            ->willReturn(['Email' => $userData['email'], 'Password' => self::PASS_HASH]);

        $us = new UserService($repoProphecy->reveal());

        $return = $us->add($userData);

        $this->assertEquals(['Email' => $userData['email'], 'Password' => self::PASS_HASH], $return);
    }

    /** @test */
    public function cannot_add_existing_user()
    {
        $userData = ['email' => 'a@b.com', 'password' => self::PASS];

        $repoProphecy = $this->prophesize(ActorUsersInterface::class);

        $repoProphecy->exists($userData['email'])
            ->willReturn(true);

        $us = new UserService($repoProphecy->reveal());

        $this->expectException(ConflictException::class);
        $return = $us->add($userData);
    }

    /** @test */
    public function can_get_a_user_from_storage()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);

        $repoProphecy->get('a@b.com')
            ->willReturn(['Email' => 'a@b.com', 'Password' => self::PASS_HASH]);

        $us = new UserService($repoProphecy->reveal());

        $return = $us->get('a@b.com');

        $this->assertEquals(['Email' => 'a@b.com', 'Password' => self::PASS_HASH], $return);
    }

    /** @test */
    public function cannot_retrieve_a_user_that_doesnt_exist()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);

        $repoProphecy->get('a@b.com')
            ->willThrow(new NotFoundException());

        $us = new UserService($repoProphecy->reveal());

        $this->expectException(NotFoundException::class);
        $return = $us->get('a@b.com');
    }

    /** @test */
    public function can_authenticate_a_user_with_valid_credentials()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);

        $repoProphecy->get('a@b.com')
            ->willReturn(['Email' => 'a@b.com', 'Password' => self::PASS_HASH]);

        $us = new UserService($repoProphecy->reveal());

        $return = $us->authenticate('a@b.com', self::PASS);

        $this->assertEquals(['Email' => 'a@b.com', 'Password' => self::PASS_HASH], $return);
    }

    /** @test */
    public function will_not_authenticate_invalid_credentials()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);

        $repoProphecy->get('a@b.com')
            ->willReturn(['Email' => 'a@b.com', 'Password' => self::PASS_HASH]);

        $us = new UserService($repoProphecy->reveal());

        $this->expectException(ForbiddenException::class);
        $return = $us->authenticate('a@b.com', 'badpassword');
    }

    /** @test */
    public function will_not_authenticate_unknown_user()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);

        $repoProphecy->get('baduser@b.com')
            ->willThrow(new NotFoundException());

        $us = new UserService($repoProphecy->reveal());

        $this->expectException(NotFoundException::class);
        $return = $us->authenticate('baduser@b.com', self::PASS);
    }

    /** @test */
    public function will_not_authenticate_unverfied_account()
    {
        $repoProphecy = $this->prophesize(ActorUsersInterface::class);

        $repoProphecy->get('a@b.com')
            ->willReturn(['Email' => 'a@b.com', 'Password' => self::PASS_HASH, 'ActivationToken' => 'aToken']);

        $us = new UserService($repoProphecy->reveal());

        $this->expectException(UnauthorizedException::class);
        $return = $us->authenticate('a@b.com', self::PASS);
    }
}