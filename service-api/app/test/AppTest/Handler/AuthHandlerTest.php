<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Handler\AuthHandler;
use App\Service\User\UserService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

class AuthHandlerTest extends TestCase
{
    // Password hash for password 'test' generated using PASSWORD_DEFAULT
    // 'test':
    const PASS_HASH = '$2y$10$Ew4y5jzm6fGKAB16huUw6ugZbuhgW5cvBQ6DGVDFzuyBXsCw51dzq';

    /** @test */
    public function can_create_an_instance()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);

        $handler = new AuthHandler($userServiceProphecy->reveal());

        $this->assertInstanceOf(AuthHandler::class, $handler);
    }

    /** @test */
    public function returns_an_error_when_bad_parameters_received()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);

        $handler = new AuthHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()
            ->willReturn([
            ]);

        $this->expectException(BadRequestException::class);
        $response = $handler->handle($requestProphecy->reveal());
    }

    /** @test */
    public function returns_a_valid_user_when_correct_parameters_received()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy->authenticate('a@b.com', 'test')
            ->willReturn([
                'Email' => 'a@b.com',
                'Password' => self::PASS_HASH
             ]);

        $handler = new AuthHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()
            ->willReturn([
                'email' => 'a@b.com',
                'password' => 'test'
            ]);

        /** @var JsonResponse $response */
        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(['Email' => 'a@b.com', 'Password' => self::PASS_HASH], $response->getPayload());
    }

    /** @test */
    public function returns_a_not_found_when_credentials_incorrect()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy->authenticate('b@c.com', 'test')
            ->willThrow(new NotFoundException());

        $handler = new AuthHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()
            ->willReturn([
                'email' => 'b@c.com',
                'password' => 'test'
            ]);

        $this->expectException(NotFoundException::class);
        $response = $handler->handle($requestProphecy->reveal());
    }
}