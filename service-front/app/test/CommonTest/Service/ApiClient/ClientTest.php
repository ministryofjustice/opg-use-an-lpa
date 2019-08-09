<?php

declare(strict_types=1);

namespace CommonTest\Service\ApiClient;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ClientTest extends TestCase
{
    /**
     * @var ObjectProphecy|ClientInterface
     */
    protected $apiClient;

    public function setUp()
    {
        $this->apiClient = $this->prophesize(ClientInterface::class);
    }

    protected function setupResponse(string $body, int $code): ObjectProphecy
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);
        $streamProphecy->getContents()
            ->willReturn($body);

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()
            ->willReturn($code);
        $responseProphecy->getBody()
            ->willReturn($streamProphecy->reveal());

        return $responseProphecy;
    }

    /** @test */
    public function can_get_a_simple_endpoint_returning_valid_json()
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', StatusCodeInterface::STATUS_OK)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', null);

        $data = $client->httpGet('/simple_get');

        $this->assertIsArray($data);
    }

    /** @test */
    public function can_get_an_endpoint_with_parameters_returning_valid_json()
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', StatusCodeInterface::STATUS_OK)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', null);

        $data = $client->httpGet('/simple_get', ['simple_query' => 'query_value']);

        $this->assertIsArray($data);
    }

    /** @test */
    public function correctly_processeses_a_non_200_response_to_a_get_request()
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('', StatusCodeInterface::STATUS_NOT_FOUND)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', null);

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $data = $client->httpGet('/simple_bad_get');
    }

    /** @test */
    public function client_throws_error_with_get_request()
    {
        $exceptionProphecy = $this->prophesize(ClientExceptionInterface::class);

        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willThrow($exceptionProphecy->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', null);

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $data = $client->httpGet('/simple_get', ['simple_query' => 'query_value']);
    }

    /** @test */
    public function sets_appropriate_request_headers()
    {
        $this->apiClient->sendRequest(Argument::that(function($request) {
            $this->assertInstanceOf(RequestInterface::class, $request);

            $headers = $request->getHeaders();
            $this->assertArrayHasKey('Accept', $headers);
            $this->assertEquals('application/json', $headers['Accept'][0]);
            $this->assertArrayHasKey('Content-Type', $headers);
            $this->assertEquals('application/json', $headers['Content-Type'][0]);
            return true;
        }))
            ->willReturn($this->setupResponse('[]', StatusCodeInterface::STATUS_OK)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', null);

        $data = $client->httpGet('/simple_get');

        $this->assertIsArray($data);
    }

    /** @test */
    public function sets_token_in_header_if_supplied()
    {
        $this->apiClient->sendRequest(Argument::that(function($request) {
            $this->assertInstanceOf(RequestInterface::class, $request);

            $headers = $request->getHeaders();
            $this->assertArrayHasKey('token', $headers);
            $this->assertEquals('test_token', $headers['token'][0]);
            return true;
        }))
            ->willReturn($this->setupResponse('[]', StatusCodeInterface::STATUS_OK)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'test_token');

        $data = $client->httpGet('/simple_get');

        $this->assertIsArray($data);
    }
}
