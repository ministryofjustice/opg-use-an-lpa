<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\ApiGateway\SignatureType;
use App\DataAccess\Repository\Response\ActorCodeExists;
use App\DataAccess\Repository\Response\ActorCodeIsValid;
use App\Exception\ApiException;
use DateTimeImmutable;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ActorCodesTest extends TestCase
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
            ->__invoke(SignatureType::None)
            ->willReturn($requestSignerProphecy->reveal());
    }

    #[Test]
    public function it_validates_a_correct_code(): void
    {
        $testData = [
            'lpa'  => 'test-uid',
            'dob'  => 'test-dob',
            'code' => 'test-code',
        ];

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);
        $responseProphecy->getBody()->willReturn(json_encode(['actor' => 'test-actor']));
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        $this->generatePSR17Prophecies($responseProphecy->reveal(), 'test-trace-id', $testData);

        $this->requestFactoryProphecy
            ->createRequest('POST', Argument::containingString('localhost/v1/validate'))
            ->willReturn($this->requestProphecy->reveal());

        $service = new ActorCodes(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id',
        );

        $actorCode = $service->validateCode($testData['code'], $testData['lpa'], $testData['dob']);

        $this->assertInstanceOf(ActorCodeIsValid::class, $actorCode->getData());
        $this->assertEquals('test-actor', $actorCode->getData()->actorUid);
    }

    #[Test]
    public function it_validates_a_correct_code_when_has_paper_verification_code(): void
    {
        $testData = [
            'lpa'  => 'test-uid',
            'dob'  => 'test-dob',
            'code' => 'test-code',
        ];

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);
        $responseProphecy->getBody()->willReturn(json_encode([
            'actor'                       => 'test-actor',
            'has_paper_verification_code' => true,
        ]));
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        $this->generatePSR17Prophecies($responseProphecy->reveal(), 'test-trace-id', $testData);

        $this->requestFactoryProphecy
            ->createRequest('POST', Argument::containingString('localhost/v1/validate'))
            ->willReturn($this->requestProphecy->reveal());

        $service = new ActorCodes(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id',
        );

        $actorCode = $service->validateCode($testData['code'], $testData['lpa'], $testData['dob']);

        $this->assertInstanceOf(ActorCodeIsValid::class, $actorCode->getData());
        $this->assertEquals('test-actor', $actorCode->getData()->actorUid);
        $this->assertTrue($actorCode->getData()->hasPaperVerificationCode);
    }

    #[Test]
    public function it_handles_a_client_exception_when_validating_a_code(): void
    {
        $testData = [
            'lpa'  => 'test-uid',
            'dob'  => 'test-dob',
            'code' => 'test-code',
        ];

        $this->generatePSR17Prophecies(
            $this->prophesize(ResponseInterface::class)->reveal(),
            'test-trace-id',
            $testData
        );

        $this->requestFactoryProphecy
            ->createRequest('POST', Argument::containingString('localhost/v1/validate'))
            ->willReturn($this->requestProphecy->reveal());

        $this->httpClientProphecy->sendRequest(Argument::any())
            ->willThrow($this->prophesize(ClientExceptionInterface::class)->reveal());

        $service = new ActorCodes(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id',
        );

        $this->expectException(ApiException::class);
        $service->validateCode($testData['code'], $testData['lpa'], $testData['dob']);
    }

    #[Test]
    public function it_handles_a_not_ok_service_error_code_when_validating_a_code(): void
    {
        $testData = [
            'lpa'  => 'test-uid',
            'dob'  => 'test-dob',
            'code' => 'test-code',
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
            ->createRequest('POST', Argument::containingString('localhost/v1/validate'))
            ->willReturn($this->requestProphecy->reveal());

        $service = new ActorCodes(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id',
        );

        $this->expectException(ApiException::class);
        $service->validateCode($testData['code'], $testData['lpa'], $testData['dob']);
    }

    #[Test]
    public function it_will_flag_a_code_as_used(): void
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);
        $responseProphecy->getBody()->willReturn('');
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        $this->generatePSR17Prophecies(
            $responseProphecy->reveal(),
            'test-trace-id',
            ['code' => 'code'],
        );

        $this->requestFactoryProphecy
            ->createRequest('POST', Argument::containingString('localhost/v1/revoke'))
            ->willReturn($this->requestProphecy->reveal());

        $service = new ActorCodes(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id',
        );

        $service->flagCodeAsUsed('code');
    }

    #[Test]
    public function it_handles_a_client_exception_when_flagging_a_code_as_used(): void
    {
        $this->generatePSR17Prophecies(
            $this->prophesize(ResponseInterface::class)->reveal(),
            'test-trace-id',
            ['code' => 'code'],
        );

        $this->requestFactoryProphecy
            ->createRequest('POST', Argument::containingString('localhost/v1/revoke'))
            ->willReturn($this->requestProphecy->reveal());

        $this->httpClientProphecy->sendRequest(Argument::any())
            ->willThrow($this->prophesize(ClientExceptionInterface::class)->reveal());

        $service = new ActorCodes(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id',
        );

        $this->expectException(ApiException::class);
        $service->flagCodeAsUsed('code');
    }

    #[Test]
    public function it_handles_a_not_ok_service_error_code_when_flagging_a_code_as_used(): void
    {
        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn('');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        $this->generatePSR17Prophecies(
            $responseProphecy->reveal(),
            'test-trace-id',
            ['code' => 'code'],
        );

        $this->requestFactoryProphecy
            ->createRequest('POST', Argument::containingString('localhost/v1/revoke'))
            ->willReturn($this->requestProphecy->reveal());

        $service = new ActorCodes(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id',
        );

        $this->expectException(ApiException::class);
        $service->flagCodeAsUsed('code');
    }

    #[Test]
    #[DataProvider('codeExistsResponse')]
    public function it_checks_whether_an_actor_has_a_code(?string $codeExistsResponse): void
    {
        $testData = [
            'lpa'   => 'test-lpa-id',
            'actor' => 'test-actor-id',
        ];

        $expectedResponse = [
            'Created' => $codeExistsResponse,
        ];

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);
        $responseProphecy->getBody()
            ->willReturn(
                json_encode($expectedResponse)
            );
        $responseProphecy->getHeaderLine('Date')->willReturn('2021-01-26T11:59:00+00:00');

        $this->generatePSR17Prophecies(
            $responseProphecy->reveal(),
            'test-trace-id',
            $testData,
        );

        $this->requestFactoryProphecy
            ->createRequest('POST', Argument::containingString('localhost/v1/exists'))
            ->willReturn($this->requestProphecy->reveal());

        $service = new ActorCodes(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id',
        );

        $actorCode = $service->checkActorHasCode($testData['lpa'], $testData['actor']);

        $this->assertInstanceOf(ActorCodeExists::class, $actorCode->getData());

        if ($codeExistsResponse !== null) {
            $this->assertEquals(
                new DateTimeImmutable($codeExistsResponse),
                $actorCode->getData()->createdAt
            );
        } else {
            $this->assertNull($actorCode->getData()->createdAt);
        }
    }

    public static function codeExistsResponse(): array
    {
        return [
            'code does not exist' => [null],
            'code exists'         => ['2021-01-01'],
        ];
    }

    #[Test]
    public function it_handles_a_client_exception_when_checking_if_a_code_exists_for_an_actor(): void
    {
        $this->generatePSR17Prophecies(
            $this->prophesize(ResponseInterface::class)->reveal(),
            'test-trace-id',
            ['lpa' => 'test-lpa-id', 'actor' => 'test-actor-id'],
        );

        $this->requestFactoryProphecy
            ->createRequest('POST', Argument::containingString('localhost/v1/exists'))
            ->willReturn($this->requestProphecy->reveal());

        $this->httpClientProphecy->sendRequest(Argument::any())
            ->willThrow($this->prophesize(ClientExceptionInterface::class)->reveal());

        $service = new ActorCodes(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id',
        );

        $this->expectException(ApiException::class);
        $service->checkActorHasCode('test-lpa-id', 'test-actor-id');
    }

    #[Test]
    public function it_handles_a_not_ok_service_error_code_when_checking_if_a_code_exists_for_an_actor(): void
    {
        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn('');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        $this->generatePSR17Prophecies(
            $responseProphecy->reveal(),
            'test-trace-id',
            ['lpa' => 'test-lpa-id', 'actor' => 'test-actor-id'],
        );

        $this->requestFactoryProphecy
            ->createRequest('POST', Argument::containingString('localhost/v1/exists'))
            ->willReturn($this->requestProphecy->reveal());

        $service = new ActorCodes(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id',
        );

        $this->expectException(ApiException::class);
        $service->checkActorHasCode('test-lpa-id', 'test-actor-id');
    }
}
