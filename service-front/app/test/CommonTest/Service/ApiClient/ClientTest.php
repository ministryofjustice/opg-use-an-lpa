<?php

declare(strict_types=1);

namespace CommonTest\Service\ApiClient;

use Common\Exception\ApiException;
use Common\Service\ApiClient\Client;
use Common\Service\Context\UserContext;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(Client::class)]
class ClientTest extends TestCase
{
    protected ClientInterface&MockObject $apiClient;

    public function setUp(): void
    {
        $this->apiClient = $this->createMock(ClientInterface::class);
    }

    protected function setupResponse(string $body, int $code): ResponseInterface
    {
        $streamProphecy = $this->createMock(StreamInterface::class);
        $streamProphecy->method('getContents')->with()
            ->willReturn($body);

        $responseProphecy = $this->createMock(ResponseInterface::class);
        $responseProphecy->method('getStatusCode')->with()
            ->willReturn($code);
        $responseProphecy->method('getBody')->with()
            ->willReturn($streamProphecy);

        return $responseProphecy;
    }

    // ============
    // httpGet
    // ============
    #[Test]
    public function can_get_a_simple_endpoint_returning_valid_json(): void
    {
        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', StatusCodeInterface::STATUS_OK));

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $data = $client->httpGet('/simple_get');

        $this->assertIsArray($data);
    }

    #[Test]
    public function can_get_an_endpoint_with_parameters_returning_valid_json(): void
    {
        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', StatusCodeInterface::STATUS_OK));

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $data = $client->httpGet('/simple_get', ['simple_query' => 'query_value']);

        $this->assertIsArray($data);
    }

    #[Test]
    public function correctly_processeses_a_non_200_response_to_a_get_request(): void
    {
        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willReturn(
                $this->setupResponse(
                    json_encode([
                        'title'   => 'Not found',
                        'details' => '',
                        'data'    => [],
                    ]),
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $data = $client->httpGet('/simple_bad_get');
    }

    #[Test]
    public function client_throws_error_with_get_request(): void
    {
        $exceptionProphecy = $this->createMock(BadResponseException::class);
        $exceptionProphecy->method('getResponse')->with()
            ->willReturn(
                $this->setupResponse(
                    json_encode([
                        'title'   => 'An API exception has occurred',
                        'details' => '',
                        'data'    => [],
                    ]),
                    StatusCodeInterface::STATUS_BAD_GATEWAY
                )
            );

        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willThrowException($exceptionProphecy);

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_GATEWAY);
        $data = $client->httpGet('/simple_get', ['simple_query' => 'query_value']);
    }

    // ============
    // httpPost
    // ============
    #[DataProvider('validStatusCodes')]
    #[Test]
    public function can_post_to_an_endpoint_with_parameters_returning_valid_json(int $statusCode): void
    {
        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', $statusCode));

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $data = $client->httpPost('/simple_post', ['simple_query' => 'query_value']);

        $this->assertIsArray($data);
    }

    #[Test]
    public function correctly_processes_a_non_2xx_response_to_a_post_request(): void
    {
        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willReturn(
                $this->setupResponse(
                    json_encode([
                        'title'   => 'An API exception has occurred',
                        'details' => '',
                        'data'    => [],
                    ]),
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $data = $client->httpPost('/simple_bad_post', ['simple_query' => 'query_value']);
    }

    #[Test]
    public function client_throws_error_with_post_request(): void
    {
        $exceptionProphecy = $this->createMock(BadResponseException::class);
        $exceptionProphecy->method('getResponse')->with()
            ->willReturn(
                $this->setupResponse(
                    json_encode([
                        'title'   => 'An API exception has occurred',
                        'details' => '',
                        'data'    => [],
                    ]),
                    StatusCodeInterface::STATUS_BAD_GATEWAY
                )
            );

        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willThrowException($exceptionProphecy);

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_GATEWAY);
        $data = $client->httpPost('/simple_post', ['simple_query' => 'query_value']);
    }

    #[Test]
    public function it_should_use_the_default_message_when_post_fails_without_a_response(): void
    {
        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willThrowException($this->createMock(ConnectException::class));

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(502);
        $this->expectExceptionMessage('Error whilst making http POST request');
        $client->httpPost('/simple_post', ['simple_query' => 'query_value']);
    }

    // ============
    // httpPut
    // ============
    #[DataProvider('validStatusCodes')]
    #[Test]
    public function can_put_to_an_endpoint_with_parameters_returning_valid_json(int $statusCode): void
    {
        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', $statusCode));

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $data = $client->httpPut('/simple_put', ['simple_query' => 'query_value']);

        $this->assertIsArray($data);
    }

    #[Test]
    public function correctly_processes_a_non_2xx_response_to_a_put_request(): void
    {
        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willReturn(
                $this->setupResponse(
                    json_encode([
                        'title'   => 'Not found',
                        'details' => '',
                        'data'    => [],
                    ]),
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $data = $client->httpPut('/simple_bad_put', ['simple_query' => 'query_value']);
    }

    #[Test]
    public function client_throws_error_with_put_request(): void
    {
        $exceptionProphecy = $this->createMock(BadResponseException::class);
        $exceptionProphecy->method('getResponse')->with()
            ->willReturn(
                $this->setupResponse(
                    json_encode([
                        'title'   => 'An API exception has occurred',
                        'details' => '',
                        'data'    => [],
                    ]),
                    StatusCodeInterface::STATUS_BAD_GATEWAY
                )
            );

        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willThrowException($exceptionProphecy);

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_GATEWAY);
        $data = $client->httpPut('/simple_put', ['simple_query' => 'query_value']);
    }

    // ============
    // httpPatch
    // ============
    #[DataProvider('validStatusCodes')]
    #[Test]
    public function can_patch_to_an_endpoint_with_parameters_returning_valid_json(int $statusCode): void
    {
        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', $statusCode));

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $data = $client->httpPatch('/simple_patch', ['simple_query' => 'query_value']);

        $this->assertIsArray($data);
    }

    #[Test]
    public function correctly_processes_a_non_2xx_response_to_a_patch_request(): void
    {
        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willReturn(
                $this->setupResponse(
                    json_encode([
                        'title'   => 'Not found',
                        'details' => '',
                        'data'    => [],
                    ]),
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $data = $client->httpPatch('/simple_bad_patch', ['simple_query' => 'query_value']);
    }

    #[Test]
    public function client_throws_error_with_patch_request(): void
    {
        $exceptionProphecy = $this->createMock(BadResponseException::class);
        $exceptionProphecy->method('getResponse')->with()
            ->willReturn(
                $this->setupResponse(
                    json_encode([
                        'title'   => 'An API exception has occurred',
                        'details' => '',
                        'data'    => [],
                    ]),
                    StatusCodeInterface::STATUS_BAD_GATEWAY
                )
            );

        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willThrowException($exceptionProphecy);

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_GATEWAY);
        $data = $client->httpPatch('/simple_patch', ['simple_query' => 'query_value']);
    }

    // ============
    // httpDelete
    // ============
    #[DataProvider('validStatusCodes')]
    #[Test]
    public function can_delete_to_an_endpoint_with_parameters_returning_valid_json(int $statusCode): void
    {
        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willReturn($this->setupResponse('[]', $statusCode));

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $data = $client->httpDelete('/simple_delete', ['simple_query' => 'query_value']);

        $this->assertIsArray($data);
    }

    #[Test]
    public function correctly_processes_a_non_2xx_response_to_a_delete_request(): void
    {
        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willReturn(
                $this->setupResponse(
                    json_encode([
                        'title'   => 'Not found',
                        'details' => '',
                        'data'    => [],
                    ]),
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $data = $client->httpDelete('/simple_bad_delete', ['simple_query' => 'query_value']);
    }

    #[Test]
    public function client_throws_error_with_delete_request(): void
    {
        $exceptionProphecy = $this->createMock(BadResponseException::class);
        $exceptionProphecy->method('getResponse')->with()
            ->willReturn(
                $this->setupResponse(
                    json_encode([
                        'title'   => 'An API exception has occurred',
                        'details' => '',
                        'data'    => [],
                    ]),
                    StatusCodeInterface::STATUS_BAD_GATEWAY
                )
            );

        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willThrowException($exceptionProphecy);

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_GATEWAY);
        $data = $client->httpDelete('/simple_delete', ['simple_query' => 'query_value']);
    }

    // ============
    // All
    // ============
    // These tests operate on all request methods in the Client class but test identical
    // expected functionality in each.
    #[Test]
    public function sets_appropriate_request_headers_for_request(): void
    {
        $this->apiClient->method('sendRequest')->with(self::callback(function ($request) {
            $this->assertInstanceOf(RequestInterface::class, $request);

            $headers = $request->getHeaders();
            $this->assertArrayHasKey('Accept', $headers);
            $this->assertEquals('application/json', $headers['Accept'][0]);
            $this->assertArrayHasKey('Content-Type', $headers);
            $this->assertEquals('application/json', $headers['Content-Type'][0]);
            return true;
        }))
            ->willReturn($this->setupResponse('[]', StatusCodeInterface::STATUS_OK));

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

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

    #[Test]
    #[DataProvider('userContexts')]
    public function it_should_set_the_user_token_header_from_the_context(
        UserContext $userContext,
        ?string $expectedToken,
        ?string $expectedSession,
    ): void {
        $this->apiClient->expects(self::exactly(5))->method('sendRequest')
            ->with(self::callback(function (RequestInterface $request) use ($expectedToken, $expectedSession): bool {
                if ($expectedToken === null || $expectedSession === null) {
                    self::assertFalse($request->hasHeader('User-Token'));
                    self::assertFalse($request->hasHeader('User-Session'));
                } else {
                    self::assertTrue($request->hasHeader('User-Token'));
                    self::assertSame($expectedToken, $request->getHeaderLine('User-Token'));
                    self::assertTrue($request->hasHeader('User-Session'));
                    self::assertSame($expectedSession, $request->getHeaderLine('User-Session'));
                }

                return true;
            }))
            ->willReturn($this->setupResponse('[]', StatusCodeInterface::STATUS_OK));

        $client = new Client($this->apiClient, 'https://localhost', '', $userContext);

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

    public static function userContexts(): array
    {
        return [
            'empty context'        => [
                new UserContext(),
                null,
                null,
            ],
            'anonymous context'    => [
                new UserContext(sessionId: 'session-id'),
                null,
                null,
            ],
            'authenticated user'   => [
                new UserContext('test_token', 'session-id'),
                'test_token',
                'session-id',
            ],
            'user without session' => [
                new UserContext('test_token'),
                null,
                null,
            ],
            'empty user id'        => [
                new UserContext('', 'session-id'),
                '',
                'session-id',
            ],
            'empty session id'     => [
                new UserContext('test_token', ''),
                'test_token',
                '',
            ],
        ];
    }

    #[Test]
    public function it_should_read_the_current_identity_from_the_injected_context(): void
    {
        $expectedTokens = [
            'first-user',
            'second-user',
            null,
        ];

        $this->apiClient->expects(self::exactly(3))->method('sendRequest')
            ->with(self::callback(function (RequestInterface $request) use (&$expectedTokens): bool {
                $expectedToken = array_shift($expectedTokens);
                self::assertSame($expectedToken !== null, $request->hasHeader('User-Token'));
                self::assertSame($expectedToken ?? '', $request->getHeaderLine('User-Token'));
                return true;
            }))
            ->willReturn($this->setupResponse('[]', StatusCodeInterface::STATUS_OK));

        $context = new UserContext(sessionId: 'session-id');
        $client  = new Client($this->apiClient, 'https://localhost', '', $context);
        foreach (['first-user', 'second-user', null] as $userId) {
            $context->userId = $userId;
            $client->httpGet('/simple_get');
        }
    }

    #[Test]
    public function sets_trace_id_in_header_if_supplied(): void
    {
        $this->apiClient->method('sendRequest')->with(self::callback(function ($request) {
            $this->assertInstanceOf(RequestInterface::class, $request);

            $headers = $request->getHeaders();
            $this->assertArrayHasKey('x-amzn-trace-id', $headers);
            $this->assertEquals('Root=1-1-11', $headers['x-amzn-trace-id'][0]);
            return true;
        }))
            ->willReturn($this->setupResponse('[]', StatusCodeInterface::STATUS_OK));

        $client = new Client($this->apiClient, 'https://localhost', 'Root=1-1-11', new UserContext('test_token'));

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

    #[Test]
    public function gracefully_handles_malformed_response_data(): void
    {
        $this->apiClient->method('sendRequest')->with(self::isInstanceOf(RequestInterface::class))
            ->willReturn($this->setupResponse('<xml>we_dont_do_xml</xml>', StatusCodeInterface::STATUS_OK));

        $client = new Client($this->apiClient, 'https://localhost', '', new UserContext());

        try {
            $data = $client->httpGet('/simple_get');
        } catch (Exception $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }

        try {
            $data = $client->httpPost('/simple_post', []);
        } catch (Exception $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }

        try {
            $data = $client->httpPut('/simple_put', []);
        } catch (Exception $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }

        try {
            $data = $client->httpPatch('/simple_patch', []);
        } catch (Exception $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }

        try {
            $data = $client->httpDelete('/simple_delete');
        } catch (Exception $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    /**
     * Provides expected valid response codes that we know our methods should handle.
     *
     * @return array
     */
    public static function validStatusCodes(): array
    {
        return [
            [StatusCodeInterface::STATUS_OK],
            [StatusCodeInterface::STATUS_CREATED],
            [StatusCodeInterface::STATUS_ACCEPTED],
            [StatusCodeInterface::STATUS_NO_CONTENT],
        ];
    }
}
