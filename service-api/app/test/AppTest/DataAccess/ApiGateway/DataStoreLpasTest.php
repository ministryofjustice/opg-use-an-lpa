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
use App\Service\Lpa\LpaDataFormatter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

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
        $this->httpClientProphecy           = $this->prophesize(ClientInterface::class);
        $this->lpaDataFormatterProphecy     = $this->prophesize(LpaDataFormatter::class);
    }

    #[Test]
    public function can_get_an_lpa(): void
    {
        $uid        = 'M-789Q-P4DF-4UX3';
        $apiBaseUri = 'http://localhost';
        $traceId    = 'test-trace-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['uid' => $uid]));

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('Wed, 16 Feb 2022 16:45:46 GMT');

        $this->generatePSR17Prophecies($responseProphecy->reveal(), $traceId, []);

        $this->requestFactoryProphecy
            ->createRequest('GET', $apiBaseUri . '/lpas/' . $uid)
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

        $shouldBeAnLPA = $moderniseLpas->get($uid);

        $this->assertInstanceOf(LpaInterface::class, $shouldBeAnLPA);
        $this->assertInstanceOf(Lpa::class, $shouldBeAnLPA->getData());
    }
    #[Test]
    public function can_lookup_multiple_lpas(): void
    {
        $uids       = ['M-789Q-P4DF-4UX3', 'M-789Q-X7DT-5PDP'];
        $apiBaseUri = 'http://localhost';
        $traceId    = 'test-trace-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy
            ->getContents()
            ->willReturn(
                json_encode(
                    [
                        'lpas' => [
                            ['uid' => 'M-789Q-P4DF-4UX3', 'registrationDate' => '2022-02-16'],
                            ['uid' => 'M-789Q-X7DT-5PDP', 'registrationDate' => null],
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
            ->__invoke(Argument::type('array'))
            ->shouldBeCalledTimes(2)
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

        $lpas = $moderniseLpas->lookup($uids);

        $this->assertCount(2, $lpas);
        foreach ($lpas as $lpa) {
            $this->assertInstanceOf(LpaInterface::class, $lpa);
            $this->assertInstanceOf(Lpa::class, $lpa->getData());
        }
    }

    #[Test]
    public function it_deals_with_a_client_error(): void
    {
        $uid        = 'M-789Q-P4DF-4UX3';
        $apiBaseUri = 'http://localhost';
        $traceId    = 'test-trace-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['uid' => $uid]));

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $this->generatePSR17Prophecies($responseProphecy->reveal(), $traceId, []);

        $this->requestFactoryProphecy
            ->createRequest('GET', $apiBaseUri . '/lpas/' . $uid)
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

        $shouldBeAnLPA = $moderniseLpas->get($uid);
    }

    #[Test]
    public function it_deals_with_a_client_error_for_multiple_lpa_fetches(): void
    {
        $uids       = ['M-789Q-P4DF-4UX3', 'M-789Q-X7DT-5PDP'];
        $apiBaseUri = 'http://localhost';
        $traceId    = 'test-trace-id';

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

        $lpas = $moderniseLpas->lookup($uids);
    }
}
