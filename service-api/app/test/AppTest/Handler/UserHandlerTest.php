<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Exception\BadRequestException;
use App\Handler\UserHandler;
use App\Service\User\UserService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class UserHandlerTest extends TestCase
{
    public function testHandleGet()
    {
        $email = 'a@b.com';

        $params = [
            'email' => $email,
        ];

        $expectedData = [
            'Email' => $email,
            'Password' => 'H@shedP@55word',
        ];

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy->getByEmail($email)
            ->willReturn($expectedData);

        //  Set up the handler
        $handler = new UserHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy->getMethod()
            ->willReturn('GET');
        $requestProphecy->getQueryParams()
            ->willReturn($params);

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

    public function testHandleGetMissingToken()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);

        //  Set up the handler
        $handler = new UserHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy->getMethod()
            ->willReturn('GET');
        $requestProphecy->getQueryParams()
            ->willReturn([]);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Email address must be provided');

        $handler->handle($requestProphecy->reveal());
    }

    public function testHandlePost()
    {
        $email = 'a@b.com';

        $requestData = [
            'email' => $email,
            'password' => 'P@55word',
        ];

        $expectedData = [
            'Email' => $email,
            'Password' => 'H@shedP@55word',
        ];

        $userServiceProphecy = $this->prophesize(UserService::class);
        $userServiceProphecy->add($requestData)
            ->willReturn($expectedData);

        //  Set up the handler
        $handler = new UserHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy->getMethod()
            ->willReturn('POST');
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

    public function testHandlePostMissingParams()
    {
        $userServiceProphecy = $this->prophesize(UserService::class);

        //  Set up the handler
        $handler = new UserHandler($userServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy->getMethod()
            ->willReturn('POST');
        $requestProphecy->getParsedBody()
            ->willReturn([]);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Email address and password must be provided');

        $handler->handle($requestProphecy->reveal());
    }
}
