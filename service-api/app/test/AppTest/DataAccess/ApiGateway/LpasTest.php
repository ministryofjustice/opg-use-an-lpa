<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\Lpas;
use App\DataAccess\Repository\DataSanitiserStrategy;
use App\DataAccess\Repository\Response\LpaInterface;
use App\Exception\ApiException;
use Aws\Credentials\Credentials;
use Aws\Signature\SignatureV4;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\StreamInterface;

/**
 * @backupGlobals enabled
 */
class LpasTest extends TestCase
{
    private string $apiUrl;
    private ObjectProphecy $dataSanitiserStrategy;
    private ObjectProphecy $httpClientProphecy;
    private ObjectProphecy $signatureV4Prophecy;
    public string $traceId;

    public function setup(): void
    {
        $this->httpClientProphecy = $this->prophesize(Client::class);
        $this->signatureV4Prophecy = $this->prophesize(SignatureV4::class);
        $this->dataSanitiserStrategy = $this->prophesize(DataSanitiserStrategy::class);
        $this->apiUrl = 'http://test';
        $this->traceId = '1234-12-12-12-1234';

        putenv('AWS_ACCESS_KEY_ID=testkey');
        putenv('AWS_SECRET_ACCESS_KEY=secretkey');
        putenv('AWS_SESSION_TOKEN=sessiontoken');
    }

    private function getLpas(): Lpas
    {
        return new Lpas(
            $this->httpClientProphecy->reveal(),
            $this->signatureV4Prophecy->reveal(),
            $this->apiUrl,
            $this->traceId,
            $this->dataSanitiserStrategy->reveal()
        );
    }

    /** @test */
    public function can_get_an_lpa(): void
    {
        $assert = $this;

        $responseProphecy = $this->prophesize(Response::class);
        $responseProphecy->getStatusCode()->willReturn(200);
        $responseProphecy->getBody()->willReturn('{ "uId": "7000-0005-5554" }');
        $responseProphecy->getHeaderLine('Date')->willReturn('Wed, 16 Feb 2022 16:45:46 GMT');

        $this->httpClientProphecy->sendAsync(Argument::type(Request::class), Argument::any())->willReturn(
            new FulfilledPromise($responseProphecy->reveal())
        );
        $this->dataSanitiserStrategy->sanitise(Argument::any())->will(function ($args) {
            return $args[0];
        });
        $this->signatureV4Prophecy
            ->signRequest(
                Argument::type(Request::class),
                Argument::type(Credentials::class)
            )->shouldBeCalled()
            ->will(function ($args) use ($assert) {
                // assert the request has trace-id
                /** @var Request $request */
                $request = $args[0];
                $assert->assertEquals($assert->traceId, $request->getHeaderLine('x-amzn-trace-id'));

                return $request;
            });

        $shouldBeAnLPA = $this->getLpas()->get('700000055554');

        $this->assertInstanceOf(LpaInterface::class, $shouldBeAnLPA);
        $this->assertEquals('7000-0005-5554', $shouldBeAnLPA->getData()['uId']);
    }

    /** @test */
    public function lpa_not_found_gives_null(): void
    {
        $assert = $this;

        $responseProphecy = $this->prophesize(Response::class);
        $responseProphecy->getStatusCode()->willReturn(404);

        $this->httpClientProphecy->sendAsync(Argument::type(Request::class), Argument::any())->willReturn(
            new FulfilledPromise($responseProphecy->reveal())
        );
        $this->dataSanitiserStrategy->sanitise(Argument::any())->will(function ($args) {
            return $args[0];
        });
        $this->signatureV4Prophecy
            ->signRequest(
                Argument::type(Request::class),
                Argument::type(Credentials::class)
            )->shouldBeCalled()
            ->will(function ($args) use ($assert) {
                // assert the request has trace-id
                /** @var Request $request */
                $request = $args[0];
                $assert->assertEquals($assert->traceId, $request->getHeaderLine('x-amzn-trace-id'));

                return $request;
            });

        $shouldBeNull = $this->getLpas()->get('700000055554');

        $this->assertNull($shouldBeNull);
    }

    /** @test */
    public function requests_a_letter_successfully(): void
    {
        $caseUid = 700000055554;
        $actorUid = 700000055554;

        $assert = $this;
        $this->signatureV4Prophecy
            ->signRequest(
                Argument::type(Request::class),
                Argument::type(Credentials::class)
            )->shouldBeCalled()
            ->will(function ($args) use ($assert) {
                // assert the request has trace-id
                /** @var Request $request */
                $request = $args[0];
                $assert->assertEquals($assert->traceId, $request->getHeaderLine('x-amzn-trace-id'));

                return $request;
            });

        /** @var MockObject|Response $dDBMock */
        $responseMock = $this->getMockBuilder(Response::class)
            ->setMethods(['getStatusCode'])
            ->disableOriginalConstructor()
            ->getMock();

        $responseMock
            ->expects($this->exactly(3))
            ->method('getStatusCode')
            ->withAnyParameters()
            ->willReturnOnConsecutiveCalls(
                $this->returnValue(StatusCodeInterface::STATUS_NO_CONTENT),
                $this->returnValue(StatusCodeInterface::STATUS_OK),
                $this->returnValue(StatusCodeInterface::STATUS_OK)
            );

        $this->httpClientProphecy
            ->send(Argument::type(Request::class))
            ->shouldBeCalled()
            ->willReturn($responseMock);

        $service = $this->getLpas();

        //Test 204 No Content response
        $service->requestLetter($caseUid, $actorUid, null);

        // Test 200 OK response
        $service->requestLetter($caseUid, $actorUid, null);

        // Test with null actor id
        $service->requestLetter($caseUid, null, "Some random string");
    }

    /** @test */
    public function requests_a_letter_with_sirius_error(): void
    {
        $caseUid = 700000055554;
        $actorUid = 700000055554;

        $this->signatureV4Prophecy
            ->signRequest(
                Argument::type(Request::class),
                Argument::type(Credentials::class)
            )->shouldBeCalled()
            ->will(function ($args) {
                return $args[0];
            });

        $contentsProphecy = $this->prophesize(StreamInterface::class);
        $contentsProphecy
            ->getContents()
            ->shouldBeCalled()
            ->willReturn("");

        $responseProphecy = $this->prophesize(Response::class);
        $responseProphecy
            ->getStatusCode()
            ->shouldBeCalled()
            ->willReturn(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $responseProphecy
            ->getBody()
            ->shouldBeCalled()
            ->willReturn($contentsProphecy->reveal());

        $this->httpClientProphecy
            ->send(Argument::type(Request::class))
            ->shouldBeCalled()
            ->willReturn($responseProphecy->reveal());

        $service = $this->getLpas();

        $this->expectException(ApiException::class);
        $service->requestLetter($caseUid, $actorUid, null);
    }

    /** @test */
    public function requests_a_letter_with_guzzle_error(): void
    {
        $caseUid = 700000055554;
        $actorUid = 700000055554;

        $this->signatureV4Prophecy
            ->signRequest(
                Argument::type(Request::class),
                Argument::type(Credentials::class)
            )->shouldBeCalled()
            ->will(function ($args) {
                return $args[0];
            });

        $this->httpClientProphecy
            ->send(Argument::type(Request::class))
            ->shouldBeCalled()
            ->willThrow($this->prophesize(GuzzleException::class)->reveal());

        $service = $this->getLpas();

        $this->expectException(ApiException::class);
        $service->requestLetter($caseUid, $actorUid, null);
    }
}
