<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\LpaSearchHandler;
use App\Service\Lpa\SiriusLpaManager;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class LpaSearchHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testHandle(): void
    {
        $this->markTestSkipped('must be revisited.');
        $params = [
            'code' => '',
            'uid'  => '',
            'dob'  => '1980-01-01',
        ];

        $expectedData = [
            'id'        => '123456789012',
            'type'      => 'property-and-financial',
            'donor'     => [],
            'attorneys' => [],
        ];

        $lpaServiceProphecy = $this->prophesize(SiriusLpaManager::class);
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

    public function testHandleMissingParams(): void
    {
        $this->markTestSkipped('must be revisited.');
        $lpaServiceProphecy = $this->prophesize(SiriusLpaManager::class);

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
