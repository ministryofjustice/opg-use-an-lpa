<?php

declare(strict_types=1);

namespace AppTest\Service\ApiClient;

use App\Exception\ApiException;
use App\Service\ApiClient\Client;
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
        // Keys from the documentation
        // https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials_environment.html
        putenv('AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE');
        putenv('AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');

        $this->apiClient = $this->prophesize(ClientInterface::class);
    }

    public function tearDown()
    {
        putenv('AWS_ACCESS_KEY_ID=');
        putenv('AWS_SECRET_ACCESS_KEY=');
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

    // ============
    // httpGet
    // ============

    /** @test */
    public function can_get_a_simple_endpoint_returning_valid_json()
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', StatusCodeInterface::STATUS_OK)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $data = $client->httpGet('/simple_get');

        $this->assertIsArray($data);
    }

    /** @test */
    public function can_get_an_endpoint_with_parameters_returning_valid_json()
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', StatusCodeInterface::STATUS_OK)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $data = $client->httpGet('/simple_get', ['simple_query' => 'query_value']);

        $this->assertIsArray($data);
    }

    /** @test */
    public function correctly_processes_a_non_200_response_to_a_get_request()
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('', StatusCodeInterface::STATUS_NOT_FOUND)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

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

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionCode(0);
        $data = $client->httpGet('/simple_get', ['simple_query' => 'query_value']);
    }

    // ============
    // httpPost
    // ============

    /**
     * @test
     * @dataProvider validStatusCodes
     */
    public function can_post_to_an_endpoint_with_parameters_returning_valid_json(int $statusCode)
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', $statusCode)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $data = $client->httpPost('/simple_post', ['simple_query' => 'query_value']);

        $this->assertIsArray($data);
    }

    /** @test */
    public function correctly_processes_a_non_2xx_response_to_a_post_request()
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('', StatusCodeInterface::STATUS_NOT_FOUND)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $data = $client->httpPost('/simple_bad_post', ['simple_query' => 'query_value']);
    }

    /** @test */
    public function client_throws_error_with_post_request()
    {
        $exceptionProphecy = $this->prophesize(ClientExceptionInterface::class);

        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willThrow($exceptionProphecy->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionCode(0);
        $data = $client->httpPost('/simple_post', ['simple_query' => 'query_value']);
    }

    // ============
    // httpPut
    // ============

    /**
     * @test
     * @dataProvider validStatusCodes
     */
    public function can_put_to_an_endpoint_with_parameters_returning_valid_json(int $statusCode)
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', $statusCode)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $data = $client->httpPut('/simple_put', ['simple_query' => 'query_value']);

        $this->assertIsArray($data);
    }

    /** @test */
    public function correctly_processes_a_non_2xx_response_to_a_put_request()
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('', StatusCodeInterface::STATUS_NOT_FOUND)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $data = $client->httpPut('/simple_bad_put', ['simple_query' => 'query_value']);
    }

    /** @test */
    public function client_throws_error_with_put_request()
    {
        $exceptionProphecy = $this->prophesize(ClientExceptionInterface::class);

        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willThrow($exceptionProphecy->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionCode(0);
        $data = $client->httpPut('/simple_put', ['simple_query' => 'query_value']);
    }

    // ============
    // httpPatch
    // ============

    /**
     * @test
     * @dataProvider validStatusCodes
     */
    public function can_patch_to_an_endpoint_with_parameters_returning_valid_json(int $statusCode)
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', $statusCode)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $data = $client->httpPatch('/simple_patch', ['simple_query' => 'query_value']);

        $this->assertIsArray($data);
    }

    /** @test */
    public function correctly_processes_a_non_2xx_response_to_a_patch_request()
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('', StatusCodeInterface::STATUS_NOT_FOUND)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $data = $client->httpPatch('/simple_bad_patch', ['simple_query' => 'query_value']);
    }

    /** @test */
    public function client_throws_error_with_patch_request()
    {
        $exceptionProphecy = $this->prophesize(ClientExceptionInterface::class);

        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willThrow($exceptionProphecy->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionCode(0);
        $data = $client->httpPatch('/simple_patch', ['simple_query' => 'query_value']);
    }

    // ============
    // httpDelete
    // ============

    /**
     * @test
     * @dataProvider validStatusCodes
     */
    public function can_delete_to_an_endpoint_with_parameters_returning_valid_json(int $statusCode)
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', $statusCode)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $data = $client->httpDelete('/simple_delete', ['simple_query' => 'query_value']);

        $this->assertIsArray($data);
    }

    /** @test */
    public function correctly_processes_a_non_2xx_response_to_a_delete_request()
    {
        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('', StatusCodeInterface::STATUS_NOT_FOUND)->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $data = $client->httpDelete('/simple_bad_delete', ['simple_query' => 'query_value']);
    }

    /** @test */
    public function client_throws_error_with_delete_request()
    {
        $exceptionProphecy = $this->prophesize(ClientExceptionInterface::class);

        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willThrow($exceptionProphecy->reveal());

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionCode(0);
        $data = $client->httpDelete('/simple_delete', ['simple_query' => 'query_value']);
    }

    // ============
    // All
    // ============

    // These tests operate on all request methods in the Client class but test identical
    // expected functionality in each.

    /** @test */
    public function sets_appropriate_request_headers_for_request()
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

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        $data = $client->httpGet('/simple_get');
        $this->assertIsArray($data);

        $data = $client->httpPost('/simple_post', []);
        $this->assertIsArray($data);

        $data = $client->httpPut('/simple_put', []);
        $this->assertIsArray($data);

        $data = $client->httpPatch('/simple_patch', []);
        $this->assertIsArray($data);

        $data = $client->httpDelete('/simple_delete');
        $this->assertIsArray($data);
    }

    /** @test */
    public function gracefully_handles_malformed_response_data()
    {
        $exceptionProphecy = $this->prophesize(ApiException::class);

        $this->apiClient->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($this->setupResponse('<xml>we_dont_do_xml</xml>', StatusCodeInterface::STATUS_OK));

        $client = new Client($this->apiClient->reveal(), 'https://localhost', 'eu-west-1');

        try {
            $data = $client->httpGet('/simple_get');
        } catch (\Exception $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }

        try {
            $data = $client->httpPost('/simple_post', []);
        } catch (\Exception $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }

        try {
            $data = $client->httpPut('/simple_put', []);
        } catch (\Exception $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }

        try {
            $data = $client->httpPatch('/simple_patch', []);
        } catch (\Exception $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }

        try {
            $data = $client->httpDelete('/simple_delete');
        } catch (\Exception $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    /**
     * Provides expected valid response codes that we know our methods should handle.
     * @return array
     */
    public function validStatusCodes(): array {
        return [
            [ StatusCodeInterface::STATUS_OK ],
            [ StatusCodeInterface::STATUS_CREATED],
            [ StatusCodeInterface::STATUS_ACCEPTED],
            [ StatusCodeInterface::STATUS_NO_CONTENT],
        ];
    }
}
