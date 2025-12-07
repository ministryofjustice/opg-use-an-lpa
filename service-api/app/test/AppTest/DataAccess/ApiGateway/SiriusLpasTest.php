<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\Repository\DataSanitiserStrategy;
use App\DataAccess\Repository\Response\LpaInterface;
use App\Entity\Lpa;
use App\Exception\ApiException;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\LpaDataFormatter;
use App\Value\LpaUid;
use Aws\EventBridge\EventBridgeClient;
use Aws\Result;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SiriusLpasTest extends TestCase
{
    use ProphecyTrait;
    use PSR17PropheciesTrait;

    private GuzzleClient|ObjectProphecy $guzzleClientProphecy;
    private DataSanitiserStrategy|ObjectProphecy $dataSanitiserStrategy;
    private LoggerInterface|ObjectProphecy $loggerInterface;
    private RequestSignerFactory|ObjectProphecy $requestSignerFactoryProphecy;
    private FeatureEnabled|ObjectProphecy $featureEnabled;
    private LpaDataFormatter|ObjectProphecy $lpaDataFormatter;
    private EventBridgeClient|ObjectProphecy $eventBridgeClient;

    public function setUp(): void
    {
        $this->guzzleClientProphecy  = $this->prophesize(GuzzleClient::class);
        $this->dataSanitiserStrategy = $this->prophesize(DataSanitiserStrategy::class);
        $this->loggerInterface       = $this->prophesize(LoggerInterface::class);
        $this->featureEnabled        = $this->prophesize(FeatureEnabled::class);
        $this->lpaDataFormatter      = $this->prophesize(LpaDataFormatter::class);
        $this->eventBridgeClient     = $this->prophesize(EventBridgeClient::class);

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
            'my test bus',
            'test-trace-id',
            $this->dataSanitiserStrategy->reveal(),
            $this->loggerInterface->reveal(),
            $this->featureEnabled->reveal(),
            $this->lpaDataFormatter->reveal(),
            $this->eventBridgeClient->reveal(),
        );
    }

    #[Test]
    public function throws_api_exception_for_request_signing_errors(): void
    {
        $this->generateCleanPSR17Prophecies();

        $this->requestSignerFactoryProphecy
            ->__invoke()
            ->willThrow($this->prophesize(NotFoundExceptionInterface::class)->reveal());

        // First of two possible exceptions that can be thrown
        try {
            $fails = $this->getLpas()->get('700000055554');
        } catch (ApiException $e) {
            $this->assertInstanceOf(NotFoundExceptionInterface::class, $e->getPrevious());
            $this->assertEquals('Unable to build a request signer instance', $e->getMessage());
        }

        $this->requestSignerFactoryProphecy
            ->__invoke()
            ->willThrow($this->prophesize(ContainerExceptionInterface::class)->reveal());

        // Second
        try {
            $fails = $this->getLpas()->get('700000055554');
        } catch (ApiException $e) {
            $this->assertInstanceOf(ContainerExceptionInterface::class, $e->getPrevious());
            $this->assertEquals('Unable to build a request signer instance', $e->getMessage());
        }
    }

    #[Test]
    public function throws_api_exception_for_body_content_errors(): void
    {
        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy
            ->getContents()
            ->willThrow(new RuntimeException());

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

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Not possible to create LPA from response data');
        $shouldBeAnLPA = $this->getLpas()->get('700000055554');
    }

    #[Test]
    public function throws_api_exception_for_formatter_hydration_errors(): void
    {
        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['uId' => '7000-0005-5554']));

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

        $this->featureEnabled
            ->__invoke('support_datastore_lpas')
            ->willReturn(true);

        $this->lpaDataFormatter
            ->__invoke(['uId' => '7000-0005-5554'])
            ->willThrow(UnableToHydrateObject::dueToError(Lpa::class));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Not possible to create LPA from response data');
        $shouldBeAnLPA = $this->getLpas()->get('700000055554');
    }

    #[Test]
    public function can_get_an_lpa(): void
    {
        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['uId' => '7000-0005-5554']));

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

        $this->dataSanitiserStrategy
            ->sanitise(Argument::any())
            ->will(function (array $args): array {
                // given the mocked code below is basically all the data sanitiser does right now
                // it could probably just be instantiated and used.
                $lpa = $args[0];
                array_walk_recursive($lpa, function (&$item, $key) {
                    if ($key === 'uId') {
                        $item = str_replace('-', '', $item);
                    }
                });

                return $lpa;
            });

        $this->featureEnabled
            ->__invoke('support_datastore_lpas')
            ->willReturn(false);

        $shouldBeAnLPA = $this->getLpas()->get('700000055554');

        $this->assertInstanceOf(LpaInterface::class, $shouldBeAnLPA);
        $this->assertEquals('700000055554', $shouldBeAnLPA->getData()['uId']);
    }

    #[Test]
    public function can_get_an_lpa_in_combined_format(): void
    {
        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['uId' => '7000-0005-5554']));

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

        $this->featureEnabled
            ->__invoke('support_datastore_lpas')
            ->willReturn(true);

        $this->lpaDataFormatter
            ->__invoke(['uId' => '7000-0005-5554'])
            ->willReturn($this->prophesize(Lpa::class)->reveal());

        $shouldBeAnLPA = $this->getLpas()->get('700000055554');

        $this->assertInstanceOf(Lpa::class, $shouldBeAnLPA->getData());
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

        $service->requestLetter(new LpaUid((string) $caseUid), $actorUid === null ? null : (string) $actorUid, $additionalInfo);
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
    public function requests_an_activation_key_successfully_for_modernised_lpa(): void
    {
        $this->generateCleanPSR17Prophecies();

        $entry = [
            'Source'       => 'opg.poas.use',
            'DetailType'   => 'activation-key-requested',
            'Detail'       => json_encode([
                'uid'   => 'M-7890-0400-4000',
                'actor' => '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d',
            ]),
            'EventBusName' => 'my test bus',
            'TraceHeader'  => 'test-trace-id',
        ];

        $this->eventBridgeClient
            ->putEvents(['Entries' => [$entry]])
            ->willReturn(new Result([]))
            ->shouldBeCalled();

        $this->loggerInterface
            ->info('Sent activation-key-requested event for {lpaUid}', Argument::any())
            ->shouldBeCalled();

        $service = $this->getLpas();

        $service->requestLetter(new LpaUid('M-7890-0400-4000'), '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d', null);
    }

    #[Test]
    public function requests_an_activation_key_for_modernised_errors(): void
    {
        $this->generateCleanPSR17Prophecies();

        $this->eventBridgeClient
            ->putEvents(Argument::any())
            ->willReturn(new Result(['FailedEntryCount' => 1]));

        $this->loggerInterface
            ->warning('Failed to put activation-key-requested event for LPA {lpaUid}', Argument::any())
            ->shouldBeCalled();

        $service = $this->getLpas();

        $this->expectException(ApiException::class);
        $service->requestLetter(new LpaUid('M-7890-0400-4000'), null, 'whatever');
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
        $service->requestLetter(new LpaUid((string) $caseUid), (string) $actorUid, null);
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
        $service->requestLetter(new LpaUid((string) $caseUid), (string) $actorUid, null);
    }
}
