<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use App\Handler\LpaHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use App\Service\Lpa\LpaService;
use Zend\Diactoros\Response\JsonResponse;

class LpaHandlerTest extends TestCase
{
    public function testHandleForId()
    {
        $uid = 12345678901;
        $shareCode = null;

        $expectedData = [
            'id'        => '12345678901',
            'type'      => 'property-and-financial',
            'donor'     => [],
            'attorneys' => [],
        ];

        $lpaServiceProphecy = $this->prophesize(LpaService::class);
        $lpaServiceProphecy->getById($uid)
            ->willReturn($expectedData);

        //  Set up the handler
        $handler = new LpaHandler($lpaServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy->getAttribute('uid')
            ->willReturn($uid);
        $requestProphecy->getAttribute('shareCode')
            ->willReturn($shareCode);

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

    public function testHandleForShareCode()
    {
        $uid = null;
        $shareCode = 123456789012;

        $expectedData = [
            'id'        => '12345678901',
            'type'      => 'property-and-financial',
            'donor'     => [],
            'attorneys' => [],
        ];

        $lpaServiceProphecy = $this->prophesize(LpaService::class);
        $lpaServiceProphecy->getByCode($shareCode)
            ->willReturn($expectedData);

        //  Set up the handler
        $handler = new LpaHandler($lpaServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy->getAttribute('uid')
            ->willReturn($uid);
        $requestProphecy->getAttribute('shareCode')
            ->willReturn($shareCode);

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
}
