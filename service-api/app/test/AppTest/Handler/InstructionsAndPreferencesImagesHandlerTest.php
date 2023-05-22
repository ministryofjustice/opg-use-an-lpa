<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\DataAccess\ApiGateway\InstructionsAndPreferencesImages;
use App\Exception\BadRequestException;
use App\Handler\InstructionsAndPreferencesImagesHandler;
use App\Service\User\UserService;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;

class InstructionsAndPreferencesImagesHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testHandleGet()
    {
        $uId = '700000000001';
        $params = [
            'uId' => $uId,
        ];

        $expectedData = [
            'uId' => '700000000001',
            'status'     => 'COLLECTION_COMPLETE',
            'signedUrls' => [
                'iap-700000000001-instructions' => 'https://image-url',
            ],
        ];

        $iapServiceProphecy = $this->prophesize(InstructionsAndPreferencesImages::class);
        $iapServiceProphecy->getInstructionsAndPreferencesImages((int)$uId)
            ->willReturn($expectedData);

        //  Set up the handler
        $handler = new InstructionsAndPreferencesImagesHandler($iapServiceProphecy->reveal());

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

    public function testHandleGetMissingUid()
    {
        $iapServiceProphecy = $this->prophesize(InstructionsAndPreferencesImages::class);

        //  Set up the handler
        $handler = new InstructionsAndPreferencesImagesHandler($iapServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy->getMethod()
            ->willReturn('GET');
        $requestProphecy->getQueryParams()
            ->willReturn([]);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Email address must be provided');

        $handler->handle($requestProphecy->reveal());
    }
}
