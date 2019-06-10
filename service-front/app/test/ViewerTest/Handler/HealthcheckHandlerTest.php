<?php

declare(strict_types=1);

namespace ViewerTest\Handler;

use Common\Service\ApiClient\Client as ApiClient;
use Viewer\Handler\HealthcheckHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class HealthcheckHandlerTest extends TestCase
{
    /**
     * @dataProvider responseDataProvider
     */
    public function testReturnsExpectedJsonResponse(int $status, array $response)
    {
        $version = 'dev';
        $apiClientProphecy = $this->prophesize(ApiClient::class);
        $apiClientProphecy->httpGet('/healthcheck')
            ->willReturn($response);

        //  Set up the handler
        $handler = new HealthcheckHandler($version, $apiClientProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $handler->handle($requestProphecy->reveal());
        $json = json_decode((string) $response->getBody()->getContents());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertObjectHasAttribute('healthy', $json);
        $this->assertEquals($version, $json->version);
        $this->assertObjectHasAttribute('dependencies', $json);

        $dependencies = $json->dependencies;
        $this->assertObjectHasAttribute('api', $dependencies);

        $api = $dependencies->api;
        $this->assertObjectHasAttribute('healthy', $api);
        $this->assertObjectHasAttribute('version', $api);
    }

    /**
     * @return string[]
     */
    public function responseDataProvider() : array
    {
        $allHealthyResponse = [
            'healthy' => true,
            'version' => 'dev',
            'dependencies' => [
                'api' => [
                    'healthy' => true,
                    'version' => 'dev'
                ]
            ]
        ];

        return [
            [ 200, $allHealthyResponse ]
        ];
    }
}
