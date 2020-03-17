<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Exception\BadRequestException;
use App\Handler\UserActivateHandler;
use App\Service\User\UserService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

class UserActivateHandlerTest extends TestCase
{
    public function testHandle()
    {
        $activationToken = 'activateTok123';

        $requestData = [
            'activation_token' => $activationToken,
        ];

        $expectedData = [
            'Email' => 'a@b.com',
            'Password' => 'H@shedP@55word',
        ];

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy->activate($activationToken)
            ->willReturn($expectedData);

        //  Set up the handler
        $handler = new UserActivateHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy->getParsedBody()
            ->willReturn($requestData);

        /** @var JsonResponse $response */
        $response = $handler->handle($requestProphecy->reveal());

        $data = $response->getPayload();

        $this->assertInstanceOf(JsonResponse::class, $response);

        //  Check the contents of the return data
        foreach ($expectedData as $fieldName => $fieldValue) {
            $this->assertArrayHasKey($fieldName, $data);
            $this->assertEquals($fieldValue, $data[$fieldName]);
        }
    }

    public function testHandleMissingToken()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);

        //  Set up the handler
        $handler = new UserActivateHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy->getParsedBody()
            ->willReturn([]);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Token must be provided');

        $handler->handle($requestProphecy->reveal());
    }
}
