<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\Repository\ActorUsersInterface;
use App\Handler\HealthcheckHandler;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HealthcheckHandlerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|ActorUsersInterface $actorUsersProphecy;
    private ObjectProphecy|ClientInterface $clientProphecy;
    private ObjectProphecy|RequestFactoryInterface $requestFactoryProphecy;
    private ObjectProphecy|RequestFactoryInterface $requestSignerFactoryProphecy;

    private string $version;
    private string $siriusApiUrl;
    private string $codesApiUrl;
    private string $iapImagesApiUrl;

    protected function setUp(): void
    {
        $this->actorUsersProphecy           = $this->prophesize(ActorUsersInterface::class);
        $this->clientProphecy               = $this->prophesize(ClientInterface::class);
        $this->requestFactoryProphecy       = $this->prophesize(RequestFactoryInterface::class);
        $this->requestSignerFactoryProphecy = $this->prophesize(RequestSignerFactory::class);
        $this->version                      = 'dev';
        $this->siriusApiUrl                 = 'localhost';
        $this->codesApiUrl                  = 'localhost';
        $this->iapImagesApiUrl              = 'localhost';
    }

    public function testReturnsExpectedJsonResponse(): void
    {
        $this->actorUsersProphecy->get('XXXXXXXXXXXX')
            ->willReturn([]);

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);

        $this->clientProphecy->sendRequest(Argument::any())
            ->shouldBeCalledTimes(3)
            ->willReturn($responseProphecy->reveal());

        $this->requestFactoryProphecy
            ->createRequest('GET', Argument::any())
            ->willReturn($this->prophesize(RequestInterface::class)->reveal());

        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::any())
            ->willReturnArgument(0);

        $this->requestSignerFactoryProphecy
            ->__invoke(Argument::any(), Argument::any())
            ->willReturn($requestSignerProphecy->reveal());

        $healthcheck = new HealthcheckHandler(
            $this->clientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            $this->actorUsersProphecy->reveal(),
            $this->version,
            $this->siriusApiUrl,
            $this->codesApiUrl,
            $this->iapImagesApiUrl
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
        $this->assertArrayHasKey('iap_images_api', $json);

        $this->assertTrue($json['lpa_api']['healthy']);
        $this->assertTrue($json['dynamo']['healthy']);
        $this->assertTrue($json['lpa_codes_api']['healthy']);
        $this->assertTrue($json['iap_images_api']['healthy']);
        $this->assertTrue($json['healthy']);
    }
}
