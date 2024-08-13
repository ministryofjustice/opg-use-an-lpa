<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use GuzzleHttp\Client as GuzzleClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\ApiGateway\SignatureType;
use App\DataAccess\Repository\DataStoreLpas;
use App\DataAccess\Repository\Response\LpaInterface;
use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\Repository\DataSanitiserStrategy;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\Argument;

class DataStoreLpasTest extends TestCase
{
    use ProphecyTrait;
    use PSR17PropheciesTrait;

    private GuzzleClient|ObjectProphecy $guzzleClientProphecy;
    private ObjectProphecy $requestSignerProphecy;
    private ObjectProphecy $requestSignerFactoryProphecy;
    private ObjectProphecy $sanitiserProphecy;

    protected function setUp(): void
    {
        $this->requestSignerFactoryProphecy = $this->prophesize(RequestSignerFactory::class);
        $this->requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $this->sanitiserProphecy = $this->prophesize(DataSanitiserStrategy::class);
        $this->guzzleClientProphecy = $this->prophesize(GuzzleClient::class);
    }
    
    #[Test]
    public function testCanGetAnLpa(): void
    {
        $uid = '700000055554';
        $apiBaseUri = 'http://localhost';
        $traceId = 'test-trace-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['uId' => $uid]));

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('Wed, 16 Feb 2022 16:45:46 GMT');

        $this->generatePSR17Prophecies($responseProphecy->reveal(), $traceId, []);

        $this->requestFactoryProphecy->createRequest('GET', $apiBaseUri . "/lpa/$uid")
            ->willReturn($this->requestProphecy->reveal());

        $this->requestSignerFactoryProphecy->__invoke(SignatureType::DataStoreLpas)
            ->willReturn($this->requestSignerProphecy->reveal());

        $this->requestSignerProphecy->sign(Argument::type(RequestInterface::class))
            ->willReturn($this->requestProphecy->reveal());

        $this->sanitiserProphecy->sanitise(Argument::any())->willReturnArgument(0);

        $this->guzzleClientProphecy
            ->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $moderniseLpas = new DataStoreLpas(
            $this->guzzleClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            $apiBaseUri,
            $traceId,
            $this->sanitiserProphecy->reveal()
        );

        $shouldBeAnLPA = $moderniseLpas->get($uid);

        $this->assertInstanceOf(LpaInterface::class, $shouldBeAnLPA);
        $this->assertEquals($uid, $shouldBeAnLPA->getData()['uId']);
    }
    #[Test]
    public function testCanLookupLpas(): void
    {
        $uids = ['700000055554', '700000055555'];
        $apiBaseUri = 'http://localhost';
        $traceId = 'test-trace-id';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()->willReturn(json_encode(['lpas' => [
            ['uId' => '700000055554', 'date' => '2022-02-16'],
            ['uId' => '700000055555', 'date' => '2022-02-16']
        ]]));

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getBody()->willReturn($responseBodyProphecy->reveal());
        $responseProphecy->getHeaderLine('Date')->willReturn('Wed, 16 Feb 2022 16:45:46 GMT');

        $this->generatePSR17Prophecies($responseProphecy->reveal(), $traceId, []);

        $this->httpClientProphecy->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $this->requestFactoryProphecy->createRequest('POST', $apiBaseUri . "/lpas")
            ->willReturn($this->requestProphecy->reveal());

        $this->streamFactoryProphecy->createStream(json_encode(['uids' => $uids]))
            ->willReturn($this->prophesize(StreamInterface::class)->reveal());

        $this->requestSignerFactoryProphecy->__invoke(SignatureType::DataStoreLpas)
            ->willReturn($this->requestSignerProphecy->reveal());

        $this->requestSignerProphecy->sign(Argument::type(RequestInterface::class))
            ->willReturn($this->requestProphecy->reveal());

        $this->sanitiserProphecy->sanitise(Argument::any())->willReturnArgument(0);

        $this->guzzleClientProphecy
            ->sendRequest(Argument::type(RequestInterface::class))
            ->willReturn($responseProphecy->reveal());

        $moderniseLpas = new DataStoreLpas(
            $this->guzzleClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            $apiBaseUri,
            $traceId,
            $this->sanitiserProphecy->reveal()
        );

        $lpas = $moderniseLpas->lookup($uids);

        $this->assertCount(2, $lpas);
        foreach ($lpas as $lpa) {
            $this->assertInstanceOf(LpaInterface::class, $lpa);
            $this->assertContains($lpa->getData()['uId'], $uids);
        }
    }
}
