<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Handler\AuthHandler;
use App\Handler\RequestPasswordResetHandler;
use App\Service\User\UserService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Util\Json;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class RequestPasswordResetHandlerTest extends TestCase
{
    /** @test */
    public function can_create_an_instance()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);

        $handler = new RequestPasswordResetHandler($userServiceProphecy->reveal());

        $this->assertInstanceOf(RequestPasswordResetHandler::class, $handler);
    }

    /** @test */
    public function returns_an_error_when_bad_parameters_received()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);

        $handler = new RequestPasswordResetHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()
            ->willReturn([
            ]);

        $this->expectException(BadRequestException::class);
        $response = $handler->handle($requestProphecy->reveal());
    }

    /** @test */
    public function returns_expected_data_when_request_successful()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy->requestPasswordReset('a@b.com')
            ->willReturn([
                'Email' => 'a@b.com',
                'PasswordResetToken' => 'resetTokenAABBCCDDEE'
            ]);

        $handler = new RequestPasswordResetHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getParsedBody()
            ->willReturn([
                'email' => 'a@b.com'
            ]);

        /** @var JsonResponse $response */
        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(JsonResponse::class, $response);

        $this->assertIsArray($response->getPayload());
        $this->assertEquals('a@b.com', $response->getPayload()['Email']);
        $this->assertEquals('resetTokenAABBCCDDEE', $response->getPayload()['PasswordResetToken']);
    }
}