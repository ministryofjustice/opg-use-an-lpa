<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\LpaHandler;
use App\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use RuntimeException;

class LpaHandlerTest extends TestCase
{
    public function testHandleForShareCode()
    {
        $this->markTestSkipped('must be revisited.');
        $shareCode = '123456789012';

        $expectedData = [
            'id'        => '123456789012',
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

    public function testHandleMissingParam()
    {
        $this->markTestSkipped('must be revisited.');
        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        //  Set up the handler
        $handler = new LpaHandler($lpaServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('shareCode')
            ->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing LPA share code');

        $handler->handle($requestProphecy->reveal());
    }
}
