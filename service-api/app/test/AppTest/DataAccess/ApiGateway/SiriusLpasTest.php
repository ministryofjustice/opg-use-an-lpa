<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\Repository\DataSanitiserStrategy;
use App\DataAccess\Repository\Response\LpaInterface;
use App\Exception\ApiException;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class SiriusLpasTest extends TestCase
{
    use ProphecyTrait;
    use PSR17PropheciesTrait;

    private GuzzleClient|ObjectProphecy $guzzleClientProphecy;
    private DataSanitiserStrategy|ObjectProphecy $dataSanitiserStrategy;
    private LoggerInterface|ObjectProphecy $loggerInterface;
    private RequestSignerFactory|ObjectProphecy $requestSignerFactoryProphecy;

    public function setUp(): void
    {
        $this->guzzleClientProphecy  = $this->prophesize(GuzzleClient::class);
        $this->dataSanitiserStrategy = $this->prophesize(DataSanitiserStrategy::class);
        $this->loggerInterface       = $this->prophesize(LoggerInterface::class);

        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::any())
            ->willReturn($this->prophesize(RequestInterface::class)->reveal());

        $this->requestSignerFactoryProphecy = $this->prophesize(RequestSignerFactory::class);
        $this->requestSignerFactoryProphecy
            ->__invoke()
            ->willReturn($requestSignerProphecy->reveal());
    }

    private function getLpas(): SiriusLpas
    {
        return new SiriusLpas(
            $this->guzzleClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id',
            $this->dataSanitiserStrategy->reveal(),
            $this->loggerInterface->reveal()
        );
    }

    #[Test]
    public function can_get_an_lpa(): void
    {
        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['uId' => '700000055554']));

        $responseProphecy = $this->prophesize(Response::class);
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('Wed, 16 Feb 2022 16:45:46 GMT');

        $this->generatePSR17Prophecies(
            $responseProphecy->reveal(),
            'test-trace-id',
            [],
        );

        $this->guzzleClientProphecy
            ->sendAsync(
                Argument::type(RequestInterface::class),
                Argument::any(),
            )->willReturn(new FulfilledPromise($responseProphecy->reveal()));

        $this->requestFactoryProphecy
            ->createRequest(
                'GET',
                Argument::containingString('localhost/v1/use-an-lpa/lpas/700000055554'),
            )->willReturn($this->requestProphecy->reveal());

        $this->dataSanitiserStrategy->sanitise(Argument::any())->willReturnArgument(0);

        $shouldBeAnLPA = $this->getLpas()->get('700000055554');

        $this->assertInstanceOf(LpaInterface::class, $shouldBeAnLPA);
        $this->assertEquals('700000055554', $shouldBeAnLPA->getData()['uId']);
    }

    #[Test]
    public function lpa_not_found_gives_null(): void
    {
        $responseProphecy = $this->prophesize(Response::class);
        $responseProphecy->getStatusCode()->willReturn(404);

        $this->generatePSR17Prophecies(
            $responseProphecy->reveal(),
            'test-trace-id',
            [],
        );

        $this->guzzleClientProphecy
            ->sendAsync(
                Argument::type(RequestInterface::class),
                Argument::any(),
            )->willReturn(new FulfilledPromise($responseProphecy->reveal()));

        $this->requestFactoryProphecy
            ->createRequest(
                'GET',
                Argument::containingString('localhost/v1/use-an-lpa/lpas/700000055554'),
            )->willReturn($this->requestProphecy->reveal());

        $this->dataSanitiserStrategy->sanitise(Argument::any())->willReturnArgument(0);

        $shouldBeNull = $this->getLpas()->get('700000055554');

        $this->assertNull($shouldBeNull);
    }

    #[Test]
    #[DataProvider('letterRequestDataProvider')]
    public function requests_a_letter_successfully(
        int $caseUid,
        int $responseCode,
        ?int $actorUid = null,
        ?string $additionalInfo = null,
    ): void {
        $testData = array_filter([
            'case_uid'  => $caseUid,
            'actor_uid' => $actorUid,
            'notes'     => $additionalInfo,
        ]);

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode([]));

        $responseProphecy = $this->prophesize(Response::class);
        $responseProphecy->getStatusCode()->willReturn($responseCode);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());

        $this->generatePSR17Prophecies(
            $responseProphecy->reveal(),
            'test-trace-id',
            $testData,
        );

        $this->guzzleClientProphecy->sendRequest(Argument::any())->willReturn($responseProphecy->reveal());

        $this->requestFactoryProphecy
            ->createRequest(
                'POST',
                Argument::containingString('localhost/v1/use-an-lpa/lpas/requestCode'),
            )->willReturn($this->requestProphecy->reveal());

        $service = $this->getLpas();

        $service->requestLetter($caseUid, $actorUid, $additionalInfo);
    }

    public static function letterRequestDataProvider(): array
    {
        return [
            '204 No Content response' => [
                'caseUid'        => 700000055554,
                'responseCode'   => StatusCodeInterface::STATUS_NO_CONTENT,
                'actorUid'       => 700000055554,
                'additionalInfo' => null,
            ],
            '200 OK response'         => [
                'caseUid'        => 700000055554,
                'responseCode'   => StatusCodeInterface::STATUS_OK,
                'actorUid'       => 700000055554,
                'additionalInfo' => null,
            ],
            'Null Actor Id'           => [
                'caseUid'        => 700000055554,
                'responseCode'   => StatusCodeInterface::STATUS_NO_CONTENT,
                'actorUid'       => null,
                'additionalInfo' => 'Some random string',
            ],
        ];
    }

    #[Test]
    public function requests_a_letter_with_sirius_error(): void
    {
        $caseUid  = 700000055554;
        $actorUid = 700000055554;

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode([]));

        $responseProphecy = $this->prophesize(Response::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());

        $this->generatePSR17Prophecies(
            $responseProphecy->reveal(),
            'test-trace-id',
            [
                'case_uid'  => $caseUid,
                'actor_uid' => $actorUid,
            ]
        );

        $this->guzzleClientProphecy->sendRequest(Argument::any())->willReturn($responseProphecy->reveal());

        $this->requestFactoryProphecy
            ->createRequest(
                'POST',
                Argument::containingString('localhost/v1/use-an-lpa/lpas/requestCode'),
            )->willReturn($this->requestProphecy->reveal());

        $service = $this->getLpas();

        $this->expectException(ApiException::class);
        $service->requestLetter($caseUid, $actorUid, null);
    }

    #[Test]
    public function requests_a_letter_with_guzzle_error(): void
    {
        $caseUid  = 700000055554;
        $actorUid = 700000055554;

        $this->generatePSR17Prophecies(
            $this->prophesize(Response::class)->reveal(),
            'test-trace-id',
            [
                'case_uid'  => $caseUid,
                'actor_uid' => $actorUid,
            ]
        );

        $this->guzzleClientProphecy
            ->sendRequest(Argument::any())
            ->willThrow($this->prophesize(GuzzleException::class)->reveal());

        $this->requestFactoryProphecy
            ->createRequest(
                'POST',
                Argument::containingString('localhost/v1/use-an-lpa/lpas/requestCode'),
            )->willReturn($this->requestProphecy->reveal());

        $service = $this->getLpas();

        $this->expectException(ApiException::class);
        $service->requestLetter($caseUid, $actorUid, null);
    }
}
