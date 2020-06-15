<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\Repository\Response\ActorCode;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client as HttpClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ActorCodesTest extends TestCase
{
    /** @test */
    public function it_validates_a_correct_code(): void
    {
        $testData = [
            'lpa'  => 'test-uid',
            'dob'  => 'test-dob',
            'code' => 'test-code'
        ];

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);
        $responseProphecy->getBody()->willReturn(json_encode($testData));
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        $httpClientProphecy = $this->prophesize(HttpClient::class);
        $httpClientProphecy->send(
            Argument::that(function (RequestInterface $request) use ($testData) {
                $this->assertEquals('POST', $request->getMethod());
                $this->assertEquals('localhost/v1/validate', $request->getUri());

                $body = (string) $request->getBody();
                $this->assertJson($body);
                $decodedBody = json_decode($body, true);
                $this->assertIsArray($decodedBody);
                $this->assertEquals($testData, $decodedBody);

                return true;
            })
        )->willReturn($responseProphecy->reveal());

        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->will(function ($args) {
                return $args[0];
            });

        $service = new ActorCodes(
            $httpClientProphecy->reveal(),
            $requestSignerProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $actorCode = $service->validateCode($testData['code'], $testData['lpa'], $testData['dob']);

        $this->assertInstanceOf(ActorCode::class, $actorCode);
        $this->assertEquals($testData, $actorCode->getData());
    }
}
