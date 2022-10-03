<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\Repository\ActorUsersInterface;
use App\Handler\HealthcheckHandler;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client as HttpClient;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HealthcheckHandlerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $actorUsersProphecy;
    private ObjectProphecy $httpClientProphecy;
    private ObjectProphecy $requestSignerProphecy;

    private string $version;
    private string $siriusApiUrl;
    private string $codesApiUrl;

    protected function setUp(): void
    {
        $this->version = 'dev';
        $this->actorUsersProphecy = $this->prophesize(ActorUsersInterface::class);
        $this->httpClientProphecy = $this->prophesize(HttpClient::class);
        $this->requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $this->siriusApiUrl = 'localhost';
        $this->codesApiUrl = 'localhost';
    }

    public function testReturnsExpectedJsonResponse(): void
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
        )
            ->shouldBeCalledTimes(2)
            ->willReturn($responseProphecy->reveal());

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
            $this->siriusApiUrl,
            $this->codesApiUrl,
        );

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $healthcheck->handle($requestProphecy->reveal());
        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());

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
