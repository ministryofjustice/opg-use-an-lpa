<?php

declare(strict_types=1);

namespace Common\Handler;

use Common\Service\ApiClient\Client as ApiClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

class HealthcheckHandlerTest extends TestCase
{
    /**
     * @dataProvider responseDataProvider
     */
    public function testReturnsExpectedJsonResponse(array $responseJson)
    {
        $version = 'dev';
        $apiClientProphecy = $this->prophesize(ApiClient::class);
        $apiClientProphecy->httpGet('/healthcheck')
            ->willReturn($responseJson);

        //  Set up the handler
        $handler = new HealthcheckHandler($version, $apiClientProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $handler->handle($requestProphecy->reveal());
        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertArrayHasKey('healthy', $json);
        $this->assertEquals($version, $json['version']);
        $this->assertArrayHasKey('dependencies', $json);

        $dependencies = $json['dependencies'];
        $this->assertArrayHasKey('api', $dependencies);

        $api = $dependencies['api'];
        $this->assertArrayHasKey('healthy', $api);
        $this->assertEquals(true, $api['healthy']);
    }

    /**
     * @return string[]
     */
    public function responseDataProvider() : array
    {
        $allHealthyResponse = [
            'version' => 'dev',
            'dependencies' => [
                'api' => [],
                'dynamo' => [],
            ],
            'healthy' => true,
            'response_time' => 0
        ];

        return [
            [$allHealthyResponse]
        ];
    }
}
