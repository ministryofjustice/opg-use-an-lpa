<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\Repository\Response\ActorCode;
use App\Exception\ApiException;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ActorCodesTest extends TestCase
{
    /** @test */
    public function it_validates_a_correct_code(): void
    {
        $testData = [
            'lpa'  => 'test-uid',
            'dob'  => 'test-dob',
            'code' => 'test-code'
        ];

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);
        $responseProphecy->getBody()->willReturn(json_encode($testData));
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        $httpClientProphecy = $this->prophesize(HttpClient::class);
        $httpClientProphecy->send(
            Argument::that(function (RequestInterface $request) use ($testData) {
                $this->assertEquals('POST', $request->getMethod());
                $this->assertEquals('localhost/v1/validate', $request->getUri());

                $body = (string) $request->getBody();
                $this->assertJson($body);
                $decodedBody = json_decode($body, true);
                $this->assertIsArray($decodedBody);
                $this->assertEquals($testData, $decodedBody);

                return true;
            })
        )->willReturn($responseProphecy->reveal());

        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->will(function ($args) {
                return $args[0];
            });

        $service = new ActorCodes(
            $httpClientProphecy->reveal(),
            $requestSignerProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $actorCode = $service->validateCode($testData['code'], $testData['lpa'], $testData['dob']);

        $this->assertInstanceOf(ActorCode::class, $actorCode);
        $this->assertEquals($testData, $actorCode->getData());
    }

    /** @test */
    public function it_handles_a_client_exception_when_validating_a_code(): void
    {
        $testData = [
            'lpa'  => 'test-uid',
            'dob'  => 'test-dob',
            'code' => 'test-code'
        ];

        $httpClientProphecy = $this->prophesize(HttpClient::class);
        $httpClientProphecy->send(Argument::type(RequestInterface::class))
            ->willThrow($this->prophesize(GuzzleException::class)->reveal());

        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->will(function ($args) {
                return $args[0];
            });

        $service = new ActorCodes(
            $httpClientProphecy->reveal(),
            $requestSignerProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $this->expectException(ApiException::class);
        $service->validateCode($testData['code'], $testData['lpa'], $testData['dob']);
    }

    /** @test */
    public function it_handles_a_not_ok_service_error_code_when_validating_a_code(): void
    {
        $testData = [
            'lpa'  => 'test-uid',
            'dob'  => 'test-dob',
            'code' => 'test-code'
        ];

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode([]));

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        $httpClientProphecy = $this->prophesize(HttpClient::class);
        $httpClientProphecy->send(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->will(function ($args) {
                return $args[0];
            });

        $service = new ActorCodes(
            $httpClientProphecy->reveal(),
            $requestSignerProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $this->expectException(ApiException::class);
        $service->validateCode($testData['code'], $testData['lpa'], $testData['dob']);
    }

    /** @test */
    public function it_will_flag_a_code_as_used(): void
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);
        $responseProphecy->getBody()->willReturn('');
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        $httpClientProphecy = $this->prophesize(HttpClient::class);
        $httpClientProphecy->send(
            Argument::that(function (RequestInterface $request) {
                $this->assertEquals('POST', $request->getMethod());
                $this->assertEquals('localhost/v1/revoke', $request->getUri());

                $body = (string) $request->getBody();
                $this->assertJson($body);
                $decodedBody = json_decode($body, true);
                $this->assertIsArray($decodedBody);
                $this->assertEquals('code', $decodedBody['code']);

                return true;
            })
        )->willReturn($responseProphecy->reveal());

        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->will(function ($args) {
                return $args[0];
            });

        $service = new ActorCodes(
            $httpClientProphecy->reveal(),
            $requestSignerProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $service->flagCodeAsUsed('code');
    }

    /** @test */
    public function it_handles_a_client_exception_when_flagging_a_code_as_used(): void
    {
        $httpClientProphecy = $this->prophesize(HttpClient::class);
        $httpClientProphecy->send(Argument::type(RequestInterface::class))
            ->willThrow($this->prophesize(GuzzleException::class)->reveal());

        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->will(function ($args) {
                return $args[0];
            });

        $service = new ActorCodes(
            $httpClientProphecy->reveal(),
            $requestSignerProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $this->expectException(ApiException::class);
        $service->flagCodeAsUsed('code');
    }

    /** @test */
    public function it_handles_a_not_ok_service_error_code_when_flagging_a_code_as_used(): void
    {
        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn('');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        $httpClientProphecy = $this->prophesize(HttpClient::class);
        $httpClientProphecy->send(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->will(function ($args) {
                return $args[0];
            });

        $service = new ActorCodes(
            $httpClientProphecy->reveal(),
            $requestSignerProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $this->expectException(ApiException::class);
        $service->flagCodeAsUsed('code');
    }

    /**
     * @test
     * @dataProvider codeExistsResponse
     */
    public function it_checks_whether_an_actor_has_a_code($codeExistsResponse): void
    {
        $testData = [
            'lpa'   => 'test-lpa-id',
            'actor' => 'test-actor-id'
        ];

        $expectedResponse = [
            'Created' => $codeExistsResponse
        ];

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);
        $responseProphecy->getBody()
            ->willReturn(
                json_encode($expectedResponse)
            );
        $responseProphecy->getHeaderLine('Date')->willReturn('2021-01-26T11:59:00+00:00');

        $httpClientProphecy = $this->prophesize(HttpClient::class);
        $httpClientProphecy->send(
            Argument::that(function (RequestInterface $request) use ($testData) {
                $this->assertEquals('POST', $request->getMethod());
                $this->assertEquals('localhost/v1/exists', $request->getUri());

                $body = (string) $request->getBody();
                $this->assertJson($body);
                $decodedBody = json_decode($body, true);
                $this->assertIsArray($decodedBody);
                $this->assertEquals($testData, $decodedBody);

                return true;
            })
        )->willReturn($responseProphecy->reveal());

        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->will(function ($args) {
                return $args[0];
            });

        $service = new ActorCodes(
            $httpClientProphecy->reveal(),
            $requestSignerProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $actorCode = $service->checkActorHasCode($testData['lpa'], $testData['actor']);

        $this->assertInstanceOf(ActorCode::class, $actorCode);
        $this->assertEquals($expectedResponse, $actorCode->getData());
    }

    public function codeExistsResponse(): array
    {
        return [
            [null],
            ['2021-01-01']
        ];
    }

    /** @test */
    public function it_handles_a_client_exception_when_checking_if_a_code_exists_for_an_actor(): void
    {
        $httpClientProphecy = $this->prophesize(HttpClient::class);
        $httpClientProphecy->send(Argument::type(RequestInterface::class))
            ->willThrow($this->prophesize(GuzzleException::class)->reveal());

        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->will(function ($args) {
                return $args[0];
            });

        $service = new ActorCodes(
            $httpClientProphecy->reveal(),
            $requestSignerProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $this->expectException(ApiException::class);
        $service->checkActorHasCode('test-lpa-id', 'test-actor-id');
    }

    /** @test */
    public function it_handles_a_not_ok_service_error_code_when_checking_if_a_code_exists_for_an_actor(): void
    {
        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn('');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        $httpClientProphecy = $this->prophesize(HttpClient::class);
        $httpClientProphecy->send(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->will(function ($args) {
                return $args[0];
            });

        $service = new ActorCodes(
            $httpClientProphecy->reveal(),
            $requestSignerProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $this->expectException(ApiException::class);
        $service->checkActorHasCode('test-lpa-id', 'test-actor-id');
    }
}
