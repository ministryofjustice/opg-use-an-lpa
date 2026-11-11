<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\AbstractApiClient;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

#[CoversClass(AbstractApiClient::class)]
final class AbstractApiClientTest extends TestCase
{
    #[Test]
    public function it_prepares_requests_without_requiring_a_legacy_trace_id(): void
    {
        $factory = new HttpFactory();
        $client  = new class (
            $this->createStub(ClientInterface::class),
            $factory,
            $factory,
            $this->createStub(RequestSignerFactory::class),
            'https://api.test',
        ) extends AbstractApiClient {
            public function prepare(RequestInterface $request): RequestInterface
            {
                return $this->attachHeaders($request);
            }
        };
        $request = new Request('POST', 'https://api.test/lpas', ['Authorization' => 'signed'], '{"lpa":"123"}');

        $prepared = $client->prepare($request);

        self::assertSame('application/vnd.opg-data.v1+json,application/json', $prepared->getHeaderLine('Accept'));
        self::assertSame('application/json', $prepared->getHeaderLine('Content-Type'));
        self::assertSame('signed', $prepared->getHeaderLine('Authorization'));
        self::assertSame('{"lpa":"123"}', (string) $prepared->getBody());
        self::assertFalse($prepared->hasHeader('x-amzn-trace-id'));
        self::assertFalse($request->hasHeader('Accept'));
    }
}
