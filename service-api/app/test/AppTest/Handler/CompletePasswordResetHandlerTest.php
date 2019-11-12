<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Exception\BadRequestException;
use App\Handler\CompletePasswordResetHandler;
use App\Service\User\UserService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class CompletePasswordResetHandlerTest extends TestCase
{
    /** @test */
    public function can_create_an_instance()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);

        $handler = new CompletePasswordResetHandler($userServiceProphecy->reveal());

        $this->assertInstanceOf(CompletePasswordResetHandler::class, $handler);
    }

    /** @test */
    public function returns_an_error_when_missing_token_received()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);

        $handler = new CompletePasswordResetHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()
            ->willReturn([
            ]);

        $this->expectException(BadRequestException::class);
        $response = $handler->handle($requestProphecy->reveal());
    }

    /** @test */
    public function returns_an_error_when_missing_password_received()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);

        $handler = new CompletePasswordResetHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()
            ->willReturn([
                'token' => '1234567890'
            ]);

        $this->expectException(BadRequestException::class);
        $response = $handler->handle($requestProphecy->reveal());
    }

    /** @test */
    public function returns_expected_data_when_request_successful()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);

        $handler = new CompletePasswordResetHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()
            ->willReturn([
                'token' => '1234567890',
                'password' => 'newpassword'
            ]);

        /** @var JsonResponse $response */
        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(JsonResponse::class, $response);

        $this->assertIsArray($response->getPayload());
    }
}