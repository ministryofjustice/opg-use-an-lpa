<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\Repository\ActorUsersInterface;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client as HttpClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Handler\HealthcheckHandler;
use Laminas\Diactoros\Response\JsonResponse;

class HealthcheckHandlerTest extends TestCase
{
    private $actorUsersProphecy;

    private $httpClientProphecy;

    private $requestSignerProphecy;

    private $apiUrl;

    private $version;

    protected function setUp()
    {
        $this->version = 'dev';
        $this->actorUsersProphecy = $this->prophesize(ActorUsersInterface::class);
        $this->httpClientProphecy = $this->prophesize(HttpClient::class);
        $this->requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $this->apiUrl = 'localhost';
    }

    public function testReturnsExpectedJsonResponse()
    {
        $this->actorUsersProphecy->get('XXXXXXXXXXXX')
            ->willReturn([]);

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);

        $this->httpClientProphecy->send(
            Argument::that(function (RequestInterface $request) {
                $this->assertEquals('GET', $request->getMethod());
                $this->assertEquals('localhost/v1/healthcheck', $request->getUri());

                return true;
            })
        )->willReturn($responseProphecy->reveal());

        $this->requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->will(function ($args) {
                return $args[0];
            });

        $healthcheck = new HealthcheckHandler(
            $this->version,
            $this->actorUsersProphecy->reveal(),
            $this->httpClientProphecy->reveal(),
            $this->requestSignerProphecy->reveal(),
            $this->apiUrl
        );

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $healthcheck->handle($requestProphecy->reveal());
        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertArrayHasKey('healthy', $json);

        $this->assertArrayHasKey('lpa_api', $json);
        $this->assertArrayHasKey('dynamo', $json);
        $this->assertArrayHasKey('lpa_codes_api', $json);

        $this->assertTrue($json['lpa_api']['healthy']);
        $this->assertTrue($json['dynamo']['healthy']);
        $this->assertTrue($json['lpa_codes_api']['healthy']);
        $this->assertTrue($json['healthy']);
    }
}
