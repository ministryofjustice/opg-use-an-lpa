<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\InstructionsAndPreferencesImages;
use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\Enum\InstructionsAndPreferencesImagesResult;
use App\Exception\ApiException;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class InstructionsAndPreferencesImagesTest extends TestCase
{
    use ProphecyTrait;
    use PSR17PropheciesTrait;

    private ObjectProphecy|RequestSignerFactory $requestSignerFactoryProphecy;

    public function setUp(): void
    {
        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::any())
            ->willReturn($this->prophesize(RequestInterface::class)->reveal());

        $this->requestSignerFactoryProphecy = $this->prophesize(RequestSignerFactory::class);
        $this->requestSignerFactoryProphecy
            ->__invoke()
            ->willReturn($requestSignerProphecy->reveal());
    }

    #[Test]
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

        $this->generatePSR17Prophecies($responseProphecy->reveal(), 'test-trace-id', $testData);

        $this->requestFactoryProphecy
            ->createRequest('GET', Argument::containingString('localhost/v1/image-request/700000000001'))
            ->willReturn($this->requestProphecy->reveal());

        $service = new InstructionsAndPreferencesImages(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
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

    #[Test]
    public function it_handles_a_client_exception_when_getting_instructions_and_preferences_images(): void
    {
        $this->generatePSR17Prophecies(
            $this->prophesize(ResponseInterface::class)->reveal(),
            'test-trace-id',
            []
        );

        $this->requestFactoryProphecy
            ->createRequest('GET', Argument::containingString('localhost/v1/image-request/700000000001'))
            ->willReturn($this->requestProphecy->reveal());

        $this->httpClientProphecy->sendRequest(Argument::any())
            ->willThrow($this->prophesize(ClientExceptionInterface::class)->reveal());

        $service = new InstructionsAndPreferencesImages(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $this->expectException(ApiException::class);
        $service->getInstructionsAndPreferencesImages(700000000001);
    }

    #[Test]
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

        $this->generatePSR17Prophecies(
            $responseProphecy->reveal(),
            'test-trace-id',
            $testData,
        );

        $this->requestFactoryProphecy
            ->createRequest('GET', Argument::containingString('localhost/v1/image-request/700000000001'))
            ->willReturn($this->requestProphecy->reveal());

        $service = new InstructionsAndPreferencesImages(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id'
        );

        $this->expectException(ApiException::class);
        $service->getInstructionsAndPreferencesImages(700000000001);
    }
}
