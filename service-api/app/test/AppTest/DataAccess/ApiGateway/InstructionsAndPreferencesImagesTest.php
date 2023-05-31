<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\InstructionsAndPreferencesImages;
use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\Repository\Response\ActorCode;
use App\DataAccess\Repository\Response\InstructionsAndPreferencesImagesResult;
use App\Exception\ApiException;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class InstructionsAndPreferencesImagesTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_gets_instructions_and_preferences_images(): void
    {
        $testData = [
            'uId'        => 700000000001,
            'status'     => 'COLLECTION_COMPLETE',
            'signedUrls' => [
                'iap-700000000001-instructions' => 'https://image-url',
            ],
        ];

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);
        $responseProphecy->getBody()->willReturn(json_encode($testData));

        $httpClientProphecy = $this->prophesize(HttpClient::class);
        $httpClientProphecy->send(
            Argument::that(function (RequestInterface $request) {
                $this->assertEquals('GET', $request->getMethod());
                $this->assertEquals('localhost/v1/image-request/700000000001', $request->getUri());

                return true;
            })
        )->willReturn($responseProphecy->reveal());

        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->will(function ($args) {
                return $args[0];
            });

        $service = new InstructionsAndPreferencesImages(
            $httpClientProphecy->reveal(),
            $requestSignerProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $instructionsAndPreferencesImages = $service->getInstructionsAndPreferencesImages($testData['uId']);

        $this->assertEquals($testData['uId'], $instructionsAndPreferencesImages->uId);
        $this->assertEquals(
            InstructionsAndPreferencesImagesResult::from($testData['status']),
            $instructionsAndPreferencesImages->status,
        );
        $this->assertEquals($testData['signedUrls'], $instructionsAndPreferencesImages->signedUrls);
    }

    /** @test */
    public function it_handles_a_client_exception_when_getting_instructions_and_preferences_images(): void
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

        $service = new InstructionsAndPreferencesImages(
            $httpClientProphecy->reveal(),
            $requestSignerProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $this->expectException(ApiException::class);
        $service->getInstructionsAndPreferencesImages(700000000001);
    }

    /** @test */
    public function it_handles_a_not_ok_service_error_code_when_getting_instructions_and_preferences_images(): void
    {
        $testData = [
            'uId'        => 700000000001,
            'status'     => 'COLLECTION_COMPLETE',
            'signedUrls' => [
                'iap-700000000001-instructions' => 'https://image-url',
            ],
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

        $service = new InstructionsAndPreferencesImages(
            $httpClientProphecy->reveal(),
            $requestSignerProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $this->expectException(ApiException::class);
        $service->getInstructionsAndPreferencesImages(700000000001);
    }
}
