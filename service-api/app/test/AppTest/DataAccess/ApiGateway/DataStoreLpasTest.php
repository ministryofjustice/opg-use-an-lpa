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
        $uid          = 'M-7890-0400-4003';
        $apiBaseUri   = 'http://localhost';
        $traceId      = 'test-trace-id';
        $originatorId = 'originator-id';

        $this->generateCleanPSR17Prophecies();

        $moderniseLpas = new DataStoreLpas(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            $this->lpaDataFormatterProphecy->reveal(),
            $apiBaseUri,
            $traceId,
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
            $this->assertSame('Unable to build a request signer instance', $e->getMessage());
        }

        $this->requestSignerFactoryProphecy
            ->__invoke(SignatureType::DataStoreLpas, Argument::type('string'))
            ->willThrow($this->prophesize(ContainerExceptionInterface::class)->reveal());

        // Second
        try {
            $moderniseLpas->setOriginatorId($originatorId)->get($uid);
        } catch (ApiException $e) {
            $this->assertInstanceOf(ContainerExceptionInterface::class, $e->getPrevious());
            $this->assertSame('Unable to build a request signer instance', $e->getMessage());
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
                $traceId,
            );

            $moderniseLpas->get($uid);
        } catch (ApiException $e) {
            $this->assertInstanceOf(OriginatorIdNotSetException::class, $e->getPrevious());
            $this->assertSame('Unable to build a request signer instance', $e->getMessage());
        }
    }

    #[Test]
    public function throws_api_exception_when_hydration_fails_on_single_lpa(): void
    {
        $uid          = 'M-7890-0400-4003';
        $apiBaseUri   = 'http://localhost';
        $traceId      = 'test-trace-id';
        $originatorId = 'originator-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['uid' => $uid]));

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('Wed, 16 Feb 2022 16:45:46 GMT');

        $this->generatePSR17Prophecies($responseProphecy->reveal(), $traceId, []);

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
            $traceId,
        );

        $this->expectException(ApiException::class);
        $moderniseLpas->setOriginatorId($originatorId)->get($uid);
    }

    #[Test]
    public function can_get_an_lpa(): void
    {
        $uid          = 'M-7890-0400-4003';
        $apiBaseUri   = 'http://localhost';
        $traceId      = 'test-trace-id';
        $originatorId = 'originator-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['uid' => $uid]));

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('Wed, 16 Feb 2022 16:45:46 GMT');

        $this->generatePSR17Prophecies($responseProphecy->reveal(), $traceId, []);

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
            $traceId,
        );

        $shouldBeAnLPA = $moderniseLpas->setOriginatorId($originatorId)->get($uid);

        $this->assertInstanceOf(LpaInterface::class, $shouldBeAnLPA);
        $this->assertInstanceOf(Lpa::class, $shouldBeAnLPA->getData());
    }

    #[Test]
    public function handles_a_not_found_response_from_the_api(): void
    {
        $uid          = 'M-7890-0400-4003';
        $apiBaseUri   = 'http://localhost';
        $traceId      = 'test-trace-id';
        $originatorId = 'originator-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn('');

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(404);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('Wed, 16 Feb 2022 16:45:46 GMT');

        $this->generatePSR17Prophecies($responseProphecy->reveal(), $traceId, []);

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
            $traceId,
        );

        $shouldBeANull = $moderniseLpas->setOriginatorId($originatorId)->get($uid);

        $this->assertNotInstanceOf(LpaInterface::class, $shouldBeANull);
    }

    #[Test]
    public function can_lookup_multiple_lpas(): void
    {
        $uids         = ['M-7890-0400-4003', 'M-789Q-X7DT-5PDP', 'M-WILL-FAIL-HYDR'];
        $apiBaseUri   = 'http://localhost';
        $traceId      = 'test-trace-id';
        $originatorId = 'originator-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy
            ->getContents()
            ->willReturn(
                json_encode(
                    [
                        'lpas' => [
                            ['uid' => 'M-7890-0400-4003', 'registrationDate' => '2022-02-16'],
                            ['uid' => 'M-789Q-X7DT-5PDP', 'registrationDate' => null],
                            ['uid' => 'M-WILL-FAIL-HYDR', 'registrationDate' => null],
                        ],
                    ]
                )
            );

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('Wed, 16 Feb 2022 16:45:46 GMT');

        $this->generatePSR17Prophecies($responseProphecy->reveal(), $traceId, []);

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

        $this->lpaDataFormatterProphecy
            ->__invoke(['uid' => 'M-7890-0400-4003', 'registrationDate' => '2022-02-16'])
            ->shouldBeCalled()
            ->willReturn($this->prophesize(Lpa::class)->reveal());

        $this->lpaDataFormatterProphecy
            ->__invoke(['uid' => 'M-789Q-X7DT-5PDP', 'registrationDate' => null])
            ->shouldBeCalled()
            ->willReturn($this->prophesize(Lpa::class)->reveal());

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
            $traceId,
        );

        $lpas = $moderniseLpas->setOriginatorId($originatorId)->lookup($uids);

        $this->assertCount(2, $lpas);
        foreach ($lpas as $lpa) {
            $this->assertInstanceOf(LpaInterface::class, $lpa);
            $this->assertInstanceOf(Lpa::class, $lpa->getData());
        }
    }

    #[Test]
    public function it_deals_with_a_response_error_during_multiple_lpa_lookup(): void
    {
        $uids         = ['M-7890-0400-4003', 'M-789Q-X7DT-5PDP'];
        $apiBaseUri   = 'http://localhost';
        $traceId      = 'test-trace-id';
        $originatorId = 'originator-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy
            ->getContents()
            ->willThrow(RuntimeException::class);

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());

        $this->generatePSR17Prophecies($responseProphecy->reveal(), $traceId, []);

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
            $traceId,
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Not possible to create LPA from response data');
        $moderniseLpas->setOriginatorId($originatorId)->lookup($uids);
    }

    #[Test]
    public function it_deals_with_a_client_error(): void
    {
        $uid          = 'M-7890-0400-4003';
        $apiBaseUri   = 'http://localhost';
        $traceId      = 'test-trace-id';
        $originatorId = 'originator-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['uid' => $uid]));

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $this->generatePSR17Prophecies($responseProphecy->reveal(), $traceId, []);

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
            $traceId,
        );

        $this->expectException(ApiException::class);

        $moderniseLpas->setOriginatorId($originatorId)->get($uid);
    }

    #[Test]
    public function it_deals_with_a_client_error_for_multiple_lpa_fetches(): void
    {
        $uids         = ['M-7890-0400-4003', 'M-789Q-X7DT-5PDP'];
        $apiBaseUri   = 'http://localhost';
        $traceId      = 'test-trace-id';
        $originatorId = 'originator-id';

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $this->generatePSR17Prophecies($responseProphecy->reveal(), $traceId, []);

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
            $traceId,
        );

        $this->expectException(ApiException::class);

        $moderniseLpas->setOriginatorId($originatorId)->lookup($uids);
    }
}
