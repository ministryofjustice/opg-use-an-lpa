<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\LpaHandler;
use App\Service\Lpa\SiriusLpaManager;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class LpaHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testHandleForShareCode(): void
    {
        $this->markTestSkipped('must be revisited.');
        $shareCode = '123456789012';

        $expectedData = [
            'id'        => '123456789012',
            'type'      => 'property-and-financial',
            'donor'     => [],
            'attorneys' => [],
        ];

        $lpaServiceProphecy = $this->prophesize(SiriusLpaManager::class);
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

    public function testHandleMissingParam(): void
    {
        $this->markTestSkipped('must be revisited.');
        $lpaServiceProphecy = $this->prophesize(SiriusLpaManager::class);

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
