<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\DataStoreLpas;
use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\ApiGateway\SignatureType;
use App\DataAccess\Repository\Response\LpaInterface;
use App\Entity\Lpa;
use App\Exception\ApiException;
use App\Exception\OriginatorIdNotSetException;
use App\Service\Lpa\LpaDataFormatter;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

#[CoversClass(DataStoreLpas::class)]
class DataStoreLpasTest extends TestCase
{
    use ProphecyTrait;
    use PSR17PropheciesTrait;

    private ClientInterface|ObjectProphecy $httpClientProphecy;
    private RequestSigner|ObjectProphecy $requestSignerProphecy;
    private RequestSignerFactory|ObjectProphecy $requestSignerFactoryProphecy;
    private LpaDataFormatter|ObjectProphecy $lpaDataFormatterProphecy;

    protected function setUp(): void
    {
        $this->requestSignerFactoryProphecy = $this->prophesize(RequestSignerFactory::class);
        $this->requestSignerProphecy        = $this->prophesize(RequestSigner::class);
        $this->lpaDataFormatterProphecy     = $this->prophesize(LpaDataFormatter::class);
    }

    #[Test]
    public function throws_api_exception_for_request_signing_errors_on_single_lpa(): void
    {
        $uid          = 'M-7890-0400-4000';
        $apiBaseUri   = 'http://localhost';
        $originatorId = 'originator-id';

        $this->generateCleanPSR17Prophecies();

        $moderniseLpas = new DataStoreLpas(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            $this->lpaDataFormatterProphecy->reveal(),
            $apiBaseUri,
        );

        $this->requestFactoryProphecy
            ->createRequest('GET', $apiBaseUri . '/lpas/' . $uid . '?presign-images')
            ->willReturn($this->requestProphecy->reveal());

        $this->requestSignerFactoryProphecy
            ->__invoke(SignatureType::DataStoreLpas, Argument::type('string'))
            ->willThrow($this->prophesize(NotFoundExceptionInterface::class)->reveal());

        // First of three possible exceptions that can be thrown
        try {
            $moderniseLpas->setOriginatorId($originatorId)->get($uid);
        } catch (ApiException $e) {
            $this->assertInstanceOf(NotFoundExceptionInterface::class, $e->getPrevious());
            $this->assertEquals('Unable to build a request signer instance', $e->getMessage());
        }

        $this->requestSignerFactoryProphecy
            ->__invoke(SignatureType::DataStoreLpas, Argument::type('string'))
            ->willThrow($this->prophesize(ContainerExceptionInterface::class)->reveal());

        // Second
        try {
            $moderniseLpas->setOriginatorId($originatorId)->get($uid);
        } catch (ApiException $e) {
            $this->assertInstanceOf(ContainerExceptionInterface::class, $e->getPrevious());
            $this->assertEquals('Unable to build a request signer instance', $e->getMessage());
        }

        // Third
        try {
            $moderniseLpas = new DataStoreLpas(
                $this->httpClientProphecy->reveal(),
                $this->requestFactoryProphecy->reveal(),
                $this->streamFactoryProphecy->reveal(),
                $this->requestSignerFactoryProphecy->reveal(),
                $this->lpaDataFormatterProphecy->reveal(),
                $apiBaseUri,
            );

            $moderniseLpas->get($uid);
        } catch (ApiException $e) {
            $this->assertInstanceOf(OriginatorIdNotSetException::class, $e->getPrevious());
            $this->assertEquals('Unable to build a request signer instance', $e->getMessage());
        }
    }

    #[Test]
    public function throws_api_exception_when_hydration_fails_on_single_lpa(): void
    {
        $uid          = 'M-7890-0400-4000';
        $apiBaseUri   = 'http://localhost';
        $originatorId = 'originator-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['uid' => $uid]));

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('Wed, 16 Feb 2022 16:45:46 GMT');

        $this->generatePSR17Prophecies($responseProphecy->reveal(), []);

        $this->requestFactoryProphecy
            ->createRequest('GET', $apiBaseUri . '/lpas/' . $uid . '?presign-images')
            ->willReturn($this->requestProphecy->reveal());

        $this->requestSignerFactoryProphecy
            ->__invoke(SignatureType::DataStoreLpas, Argument::type('string'))
            ->willReturn($this->requestSignerProphecy->reveal());

        $this->requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->willReturn($this->requestProphecy->reveal());

        $this->httpClientProphecy
            ->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $this->lpaDataFormatterProphecy
            ->__invoke(['uid' => $uid])
            ->willThrow(UnableToHydrateObject::dueToError('Mock error'));

        $moderniseLpas = new DataStoreLpas(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            $this->lpaDataFormatterProphecy->reveal(),
            $apiBaseUri,
        );

        $this->expectException(ApiException::class);
        $moderniseLpas->setOriginatorId($originatorId)->get($uid);
    }

    #[Test]
    public function can_get_an_lpa(): void
    {
        $uid          = 'M-7890-0400-4000';
        $apiBaseUri   = 'http://localhost';
        $originatorId = 'originator-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['uid' => $uid]));

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('Wed, 16 Feb 2022 16:45:46 GMT');

        $this->generatePSR17Prophecies($responseProphecy->reveal(), []);

        $this->requestFactoryProphecy
            ->createRequest('GET', $apiBaseUri . '/lpas/' . $uid . '?presign-images')
            ->willReturn($this->requestProphecy->reveal());

        $this->requestSignerFactoryProphecy
            ->__invoke(SignatureType::DataStoreLpas, Argument::type('string'))
            ->willReturn($this->requestSignerProphecy->reveal());

        $this->requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->willReturn($this->requestProphecy->reveal());

        $this->httpClientProphecy
            ->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $this->lpaDataFormatterProphecy
            ->__invoke(['uid' => $uid])
            ->willReturn($this->prophesize(Lpa::class)->reveal());

        $moderniseLpas = new DataStoreLpas(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            $this->lpaDataFormatterProphecy->reveal(),
            $apiBaseUri,
        );

        $shouldBeAnLPA = $moderniseLpas->setOriginatorId($originatorId)->get($uid);

        $this->assertInstanceOf(LpaInterface::class, $shouldBeAnLPA);
        $this->assertInstanceOf(Lpa::class, $shouldBeAnLPA->getData());
    }

    #[Test]
    public function handles_a_not_found_response_from_the_api(): void
    {
        $uid          = 'M-7890-0400-4000';
        $apiBaseUri   = 'http://localhost';
        $originatorId = 'originator-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn('');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(404);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('Wed, 16 Feb 2022 16:45:46 GMT');

        $this->generatePSR17Prophecies($responseProphecy->reveal(), []);

        $this->requestFactoryProphecy
            ->createRequest('GET', $apiBaseUri . '/lpas/' . $uid . '?presign-images')
            ->willReturn($this->requestProphecy->reveal());

        $this->requestSignerFactoryProphecy
            ->__invoke(SignatureType::DataStoreLpas, Argument::type('string'))
            ->willReturn($this->requestSignerProphecy->reveal());

        $this->requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->willReturn($this->requestProphecy->reveal());

        $this->httpClientProphecy
            ->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $moderniseLpas = new DataStoreLpas(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            $this->lpaDataFormatterProphecy->reveal(),
            $apiBaseUri,
        );

        $shouldBeANull = $moderniseLpas->setOriginatorId($originatorId)->get($uid);

        $this->assertNull($shouldBeANull);
    }

    #[Test]
    public function can_lookup_multiple_lpas(): void
    {
        $apiBaseUri   = 'http://localhost';
        $originatorId = 'originator-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy
            ->getContents()
            ->willReturn(
                json_encode(
                    [
                        'lpas' => [
                            [
                                'uid'              => 'M-7890-0400-4000',
                                'registrationDate' => '2022-02-16',
                            ],
                            [
                                'uid'              => 'M-789Q-X7DT-5PDP',
                                'registrationDate' => null,
                            ],
                            [
                                'uid'              => 'M-WILL-FAIL-HYDR',
                                'registrationDate' => null,
                            ],
                        ],
                    ]
                )
            );

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('Wed, 16 Feb 2022 16:45:46 GMT');

        $this->generatePSR17Prophecies($responseProphecy->reveal(), []);

        $this->httpClientProphecy->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $this->requestFactoryProphecy->createRequest('POST', $apiBaseUri . '/lpas')
            ->willReturn($this->requestProphecy->reveal());

        $this->streamFactoryProphecy
            ->createStream(json_encode([
                'uids' => [
                    'M-7890-0400-4000',
                    'M-789Q-X7DT-5PDP',
                    'M-WILL-FAIL-HYDR',
                ],
            ]))
            ->willReturn($this->prophesize(StreamInterface::class)->reveal());

        $this->requestSignerFactoryProphecy->__invoke(SignatureType::DataStoreLpas, Argument::type('string'))
            ->willReturn($this->requestSignerProphecy->reveal());

        $this->requestSignerProphecy->sign(Argument::type(RequestInterface::class))
            ->willReturn($this->requestProphecy->reveal());

        $this->httpClientProphecy
            ->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $this->lpaDataFormatterProphecy
            ->__invoke(['uid' => 'M-7890-0400-4000', 'registrationDate' => '2022-02-16'])
            ->shouldBeCalled()
            ->willReturn($this->lpaWithUid('M-7890-0400-4000'));

        $this->lpaDataFormatterProphecy
            ->__invoke(['uid' => 'M-789Q-X7DT-5PDP', 'registrationDate' => null])
            ->shouldBeCalled()
            ->willReturn($this->lpaWithUid('M-789Q-X7DT-5PDP'));

        // Handles a Hydration error
        $this->lpaDataFormatterProphecy
            ->__invoke(['uid' => 'M-WILL-FAIL-HYDR', 'registrationDate' => null])
            ->shouldBeCalled()
            ->willThrow(UnableToHydrateObject::dueToError('Mock error'));

        $moderniseLpas = new DataStoreLpas(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            $this->lpaDataFormatterProphecy->reveal(),
            $apiBaseUri,
        );

        $lpas = $moderniseLpas->setOriginatorId($originatorId)->lookup([
            'M-7890-0400-4000',
            'M-789Q-X7DT-5PDP',
            'M-WILL-FAIL-HYDR',
            'M-7890-0400-4000',
            'M-789Q-X7DT-5PDP',
        ]);

        $this->assertCount(2, $lpas);
        foreach ($lpas as $lpa) {
            $this->assertInstanceOf(LpaInterface::class, $lpa);
            $this->assertInstanceOf(Lpa::class, $lpa->getData());
        }
    }

    #[Test]
    public function it_deals_with_a_response_error_during_multiple_lpa_lookup(): void
    {
        $uids         = [
            'M-7890-0400-4000',
            'M-789Q-X7DT-5PDP',
        ];
        $apiBaseUri   = 'http://localhost';
        $originatorId = 'originator-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy
            ->getContents()
            ->willThrow(RuntimeException::class);

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());

        $this->generatePSR17Prophecies($responseProphecy->reveal(), []);

        $this->httpClientProphecy->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $this->requestFactoryProphecy->createRequest('POST', $apiBaseUri . '/lpas')
            ->willReturn($this->requestProphecy->reveal());

        $this->streamFactoryProphecy->createStream(json_encode(['uids' => $uids]))
            ->willReturn($this->prophesize(StreamInterface::class)->reveal());

        $this->requestSignerFactoryProphecy->__invoke(SignatureType::DataStoreLpas, Argument::type('string'))
            ->willReturn($this->requestSignerProphecy->reveal());

        $this->requestSignerProphecy->sign(Argument::type(RequestInterface::class))
            ->willReturn($this->requestProphecy->reveal());

        $this->httpClientProphecy
            ->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $moderniseLpas = new DataStoreLpas(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            $this->lpaDataFormatterProphecy->reveal(),
            $apiBaseUri,
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Not possible to create LPA from response data');
        $moderniseLpas->setOriginatorId($originatorId)->lookup($uids);
    }

    #[Test]
    public function it_deals_with_a_client_error(): void
    {
        $uid          = 'M-7890-0400-4000';
        $apiBaseUri   = 'http://localhost';
        $originatorId = 'originator-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['uid' => $uid]));

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $this->generatePSR17Prophecies($responseProphecy->reveal(), []);

        $this->requestFactoryProphecy
            ->createRequest('GET', $apiBaseUri . '/lpas/' . $uid . '?presign-images')
            ->willReturn($this->requestProphecy->reveal());

        $this->requestSignerFactoryProphecy
            ->__invoke(SignatureType::DataStoreLpas, Argument::type('string'))
            ->willReturn($this->requestSignerProphecy->reveal());

        $this->requestSignerProphecy
            ->sign(Argument::type(RequestInterface::class))
            ->willReturn($this->requestProphecy->reveal());

        $this->httpClientProphecy
            ->sendRequest(Argument::type(RequestInterface::class))
            ->willThrow($this->prophesize(ClientExceptionInterface::class)->reveal());

        $this->lpaDataFormatterProphecy
            ->__invoke(['uid' => $uid])
            ->willReturn($this->prophesize(Lpa::class)->reveal());

        $moderniseLpas = new DataStoreLpas(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            $this->lpaDataFormatterProphecy->reveal(),
            $apiBaseUri,
        );

        $this->expectException(ApiException::class);

        $moderniseLpas->setOriginatorId($originatorId)->get($uid);
    }

    #[Test]
    public function it_deals_with_a_client_error_for_multiple_lpa_fetches(): void
    {
        $uids         = [
            'M-7890-0400-4000',
            'M-789Q-X7DT-5PDP',
        ];
        $apiBaseUri   = 'http://localhost';
        $originatorId = 'originator-id';

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $this->generatePSR17Prophecies($responseProphecy->reveal(), []);

        $this->httpClientProphecy->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $this->requestFactoryProphecy->createRequest('POST', $apiBaseUri . '/lpas')
            ->willReturn($this->requestProphecy->reveal());

        $this->streamFactoryProphecy->createStream(json_encode(['uids' => $uids]))
            ->willReturn($this->prophesize(StreamInterface::class)->reveal());

        $this->requestSignerFactoryProphecy->__invoke(SignatureType::DataStoreLpas, Argument::type('string'))
            ->willReturn($this->requestSignerProphecy->reveal());

        $this->requestSignerProphecy->sign(Argument::type(RequestInterface::class))
            ->willReturn($this->requestProphecy->reveal());

        $this->httpClientProphecy
            ->sendRequest(Argument::type(RequestInterface::class))
            ->willThrow($this->prophesize(ClientExceptionInterface::class)->reveal());

        $moderniseLpas = new DataStoreLpas(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            $this->lpaDataFormatterProphecy->reveal(),
            $apiBaseUri,
        );

        $this->expectException(ApiException::class);

        $moderniseLpas->setOriginatorId($originatorId)->lookup($uids);
    }

    #[Test]
    public function it_should_invoke_the_callback_for_each_lpa_and_wait_for_its_promise(): void
    {
        $handler = new MockHandler([
            new Response(
                StatusCodeInterface::STATUS_OK,
                ['Date' => 'Wed, 16 Feb 2022 16:45:46 GMT'],
                json_encode(['lpas' => [['uid' => 'M-0000-0000-0001'], ['uid' => 'M-0000-0000-0002']]]),
            ),
        ]);

        $lpaOne = $this->createConfiguredStub(Lpa::class, ['getUid' => 'M-0000-0000-0001']);
        $lpaTwo = $this->createConfiguredStub(Lpa::class, ['getUid' => 'M-0000-0000-0002']);

        $formatter = $this->createMock(LpaDataFormatter::class);
        $formatter
            ->method('__invoke')
            ->willReturnCallback(fn (array $data): Lpa => $data['uid'] === 'M-0000-0000-0001' ? $lpaOne : $lpaTwo);

        $seen   = [];
        $result = $this->createDataStoreLpas($handler, $formatter)
            ->lookupAsync(
                [
                    'M-0000-0000-0001',
                    'M-0000-0000-0002',
                ],
                function (LpaInterface $lpa, string $uid) use (&$seen): PromiseInterface {
                    return Create::promiseFor(null)->then(function () use ($lpa, $uid, &$seen): void {
                        $seen[$uid] = $lpa->getData();
                    });
                },
            )
            ->wait();

        $this->assertSame([$lpaOne, $lpaTwo], array_map(fn (LpaInterface $lpa) => $lpa->getData(), $result));
        $this->assertSame(['M-0000-0000-0001' => $lpaOne, 'M-0000-0000-0002' => $lpaTwo], $seen);
    }

    #[Test]
    public function it_should_reject_with_an_api_exception_for_a_non_ok_response(): void
    {
        $handler = new MockHandler([new Response(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR)]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('LPA data store returned non-ok response');

        $this->createDataStoreLpas($handler, $this->createStub(LpaDataFormatter::class))
            ->lookupAsync(['M-0000-0000-0001'])
            ->wait();
    }

    #[Test]
    public function it_should_reject_with_an_api_exception_for_a_transport_failure(): void
    {
        $handler = new MockHandler([new ConnectException('Connection refused', new Request('POST', 'localhost'))]);

        try {
            $this->createDataStoreLpas($handler, $this->createStub(LpaDataFormatter::class))
                ->lookupAsync(['M-0000-0000-0001'])
                ->wait();
            $this->fail('Expected an ApiException');
        } catch (ApiException $exception) {
            $this->assertSame('Error whilst communicating with LPA data store', $exception->getMessage());
            $this->assertInstanceOf(ConnectException::class, $exception->getPrevious());
        }
    }

    #[Test]
    public function it_should_not_wrap_errors_that_are_not_client_errors(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willThrowException(new RuntimeException('Unexpected'));

        $signer = $this->createMock(RequestSigner::class);
        $signer->method('sign')->willReturnArgument(0);

        $signerFactory = $this->createMock(RequestSignerFactory::class);
        $signerFactory->method('__invoke')->willReturn($signer);

        $sut = new DataStoreLpas(
            $client,
            new HttpFactory(),
            new HttpFactory(),
            $signerFactory,
            $this->createStub(LpaDataFormatter::class),
            'http://localhost',
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unexpected');

        $sut->setOriginatorId('originator-id')->lookupAsync(['M-0000-0000-0001'])->wait();
    }

    private function createDataStoreLpas(MockHandler $handler, LpaDataFormatter $formatter): DataStoreLpas
    {
        $signer = $this->createMock(RequestSigner::class);
        $signer->method('sign')->willReturnArgument(0);

        $signerFactory = $this->createMock(RequestSignerFactory::class);
        $signerFactory->method('__invoke')->willReturn($signer);

        return (new DataStoreLpas(
            new GuzzleClient(['handler' => HandlerStack::create($handler)]),
            new HttpFactory(),
            new HttpFactory(),
            $signerFactory,
            $formatter,
            'http://localhost',
        ))->setOriginatorId('originator-id');
    }

    private function lpaWithUid(string $uid): Lpa
    {
        $lpa = $this->prophesize(Lpa::class);
        $lpa->getUid()->willReturn($uid);

        return $lpa->reveal();
    }
}
