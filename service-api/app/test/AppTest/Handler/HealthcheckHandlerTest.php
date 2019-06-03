<?php

declare(strict_types=1);

namespace AppTest\Handler;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use App\Handler\HealthcheckHandler;
use Zend\Diactoros\Response\JsonResponse;
use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class HealthcheckHandlerTest extends TestCase
{
    protected function apiHealthcheckResponse(int $status = 200, string $response) : HttpClient
    {
        $bodyProphecy = $this->prophesize(StreamInterface::class);
        $bodyProphecy->getContents()
            ->willReturn($response);

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()
            ->willReturn($status);
        $responseProphecy->getBody()
            ->willReturn($bodyProphecy->reveal());

        $httpClientProphecy = $this->prophesize(HttpClient::class);
        $httpClientProphecy->sendRequest(new CallbackToken(function($request) {
            $this->assertInstanceOf(RequestInterface::class, $request);

            return true;
        }))
            ->willReturn($responseProphecy->reveal());

        return $httpClientProphecy->reveal();
    }

    /**
     * @dataProvider responseDataProvider
     */
    public function testReturnsExpectedJsonResponse(int $status, string $response)
    {
        $this->markTestIncomplete(
            'This test needs finishing once we know what we want back from the Gateway'
        );

        //  Set up the handler
        $handler = new HealthcheckHandler($this->apiHealthcheckResponse($status, $response));

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $handler->handle($requestProphecy->reveal());
        $json = json_decode((string) $response->getBody()->getContents());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertObjectHasAttribute('healthy', $json);
        $this->assertObjectHasAttribute('version', $json);
        $this->assertObjectHasAttribute('dependencies', $json);

        $dependencies = $json->dependencies;
        $this->assertObjectHasAttribute('api_gateway', $dependencies);

        $api = $dependencies->api_gateway;
        $this->assertObjectHasAttribute('healthy', $api);
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
                'api_gateway' => [
                    'healthy' => true,
                    'version' => 'dev'
                ]
            ]
        ];

        return [
            [ 200, json_encode($allHealthyResponse) ]
        ];
    }
}
