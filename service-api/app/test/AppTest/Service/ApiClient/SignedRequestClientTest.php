<?php

declare(strict_types=1);

namespace AppTest\Service\ApiClient;

use App\Service\ApiClient\SignedRequestClient;
use Aws\Credentials\CredentialProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\CallbackToken;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class SignedRequestClientTest extends TestCase
{
    public function setUp()
    {
        // this is not an integration test so set dummy env values.
        putenv(CredentialProvider::ENV_KEY . '=test');
        putenv(CredentialProvider::ENV_SECRET . '=test');

        parent::setUp();
    }

    public function testBasicGetRequest()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()
            ->willReturn(200);
        $responseProphecy->getBody()
            ->willReturn('{"test":"test"}');

        $clientProphecy = $this->prophesize(ClientInterface::class);
        $clientProphecy->sendRequest(new CallbackToken(function(RequestInterface $request) {
            $this->assertInstanceOf(RequestInterface::class, $request);

            $this->assertEquals('GET', $request->getMethod());
            $this->assertArrayHasKey('Authorization', $request->getHeaders());
            $this->assertEquals('localhost/test', $request->getUri()->getPath());

            return true;
        }))
            ->willReturn($responseProphecy->reveal());

        $src = new SignedRequestClient($clientProphecy->reveal(), 'localhost', 'eu-west-1');

        $response = $src->httpGet('/test');


    }
}