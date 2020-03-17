<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\LpaSearchHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use App\Service\Lpa\LpaService;
use Laminas\Diactoros\Response\JsonResponse;
use RuntimeException;

class LpaSearchHandlerTest extends TestCase
{
    public function testHandle()
    {
        $this->markTestSkipped('must be revisited.');
        $params = [
            'code' => '',
            'uid' => '',
            'dob' => '1980-01-01',
        ];

        $expectedData = [
            'id'        => '123456789012',
            'type'      => 'property-and-financial',
            'donor'     => [],
            'attorneys' => [],
        ];

        $lpaServiceProphecy = $this->prophesize(LpaService::class);
        $lpaServiceProphecy->search($params['code'], $params['uid'], $params['dob'])
            ->willReturn($expectedData);

        //  Set up the handler
        $handler = new LpaSearchHandler($lpaServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy->getQueryParams()
            ->willReturn($params);

        /** @var JsonResponse $response */
        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = $response->getPayload();

        //  Check the contents of the return data
        foreach ($expectedData as $fieldName => $fieldValue) {
            $this->assertArrayHasKey($fieldName, $data);
            $this->assertEquals($fieldValue, $data[$fieldName]);
        }
    }
    public function testHandleMissingParams()
    {
        $this->markTestSkipped('must be revisited.');
        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        //  Set up the handler
        $handler = new LpaSearchHandler($lpaServiceProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $requestProphecy->getQueryParams()
            ->willReturn([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing LPA search parameters');

        $handler->handle($requestProphecy->reveal());
    }
}
