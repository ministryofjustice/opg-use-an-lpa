<?php

declare(strict_types=1);

namespace Common\Handler;

use Common\Service\ApiClient\Client as ApiClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

class HealthcheckHandlerTest extends TestCase
{
    public function testReturnsExpectedJsonResponse()
    {
        $healthyResponse = [
            'sirius_api' => ['healthy' => true],
            'dynamo' => ['healthy' => true],
            'lpa_codes_api' => ['healthy' => true],
            'healthy' => true
        ];

        $version = 'dev';
        $apiClientProphecy = $this->prophesize(ApiClient::class);
        $apiClientProphecy->httpGet('/healthcheck')
            ->willReturn($healthyResponse);

        $handler = new HealthcheckHandler($version, $apiClientProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $handler->handle($requestProphecy->reveal());
        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertArrayHasKey('overall_healthy', $json);
        $this->assertTrue($json['overall_healthy']);

        $this->assertEquals($version, $json['version']);
        $this->assertArrayHasKey('dependencies', $json);

        $dependencies = $json['dependencies'];

        $api = $dependencies['sirius_api'];
        $this->assertArrayHasKey('healthy', $api);
        $this->assertTrue($api['healthy']);

        $dynamo = $dependencies['dynamo'];
        $this->assertArrayHasKey('healthy', $dynamo);
        $this->assertTrue($dynamo['healthy']);

        $lpaCodesApi = $dependencies['lpa_codes_api'];
        $this->assertArrayHasKey('healthy', $lpaCodesApi);
        $this->assertTrue($lpaCodesApi['healthy']);

        $this->assertTrue($dependencies['healthy']);
    }
}
