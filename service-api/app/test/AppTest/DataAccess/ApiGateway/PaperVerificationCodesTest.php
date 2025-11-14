<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\PaperVerificationCodes;
use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\Repository\Response\PaperVerificationCode as PaperVerificationCodeResponse;
use App\DataAccess\Repository\Response\PaperVerificationCodeExpiry;
use App\Enum\VerificationCodeExpiryReason;
use App\Value\PaperVerificationCode;
use DateInterval;
use DateTimeImmutable;
use Fig\Http\Message\StatusCodeInterface;
use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[CoversClass(PaperVerificationCodes::class)]
#[CoversClass(PaperVerificationCode::class)]
class PaperVerificationCodesTest extends TestCase
{
    use ProphecyTrait;
    use PSR17PropheciesTrait;

    private ObjectProphecy|RequestSignerFactory $requestSignerFactoryProphecy;

    protected function setUp(): void
    {
        $requestSignerProphecy = $this->prophesize(RequestSigner::class);
        $requestSignerProphecy
            ->sign(Argument::any())
            ->willReturn($this->prophesize(RequestInterface::class)->reveal());

        $this->requestSignerFactoryProphecy = $this->prophesize(RequestSignerFactory::class);
        $this->requestSignerFactoryProphecy
            ->__invoke(Argument::any())
            ->willReturn($requestSignerProphecy->reveal());
    }

    /**
     * @param array{
     *      lpa: string,
     *      expires?: string,
     *      cancelled?: string
     * }  $response
     */
    #[Test]
    #[DataProvider('codeDataProvider')]
    public function it_fetches_codes_with_differing_upstream_response_fields(string $code, array $response): void
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);
        $responseProphecy->getBody()->willReturn(json_encode($response));
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        $this->generatePSR17Prophecies(
            $responseProphecy->reveal(),
            'test-trace-id',
            [
                'code' => $code,
            ]
        );

        $this->requestFactoryProphecy
            ->createRequest(
                'POST',
                Argument::containingString('localhost/v1/paper-verification-code/validate'),
                Argument::any()
            )
            ->willReturn($this->requestProphecy->reveal());

        $sut = new PaperVerificationCodes(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id',
        );

        $data = $sut->validate(new PaperVerificationCode($code))->getData();

        $this->assertInstanceOf(PaperVerificationCodeResponse::class, $data);
        $this->assertEquals($response['lpa'], (string)$data->lpaUid);

        if (isset($response['expiry_date'])) {
            $this->assertEqualsWithDelta(new DateTimeImmutable($response['expiry_date']), $data->expiresAt, 5);
        }

        if (isset($response['expiry_reason'])) {
            $this->assertEquals($response['expiry_reason'], $data->expiryReason->value);
        }
    }

    public static function codeDataProvider(): Iterator
    {
        yield [
            'P-1234-1234-1234-12',
            [
                'lpa' => 'M-7890-0400-4003', // no expiry as it's not been used yet
            ],
        ];
        yield [
            'P-5678-5678-5678-56',
            [
                'lpa'           => 'M-7890-0400-4003',
                'expiry_date'   => (new DateTimeImmutable())
                    ->sub(new DateInterval('P1Y')) // code has expired
                    ->format('Y-m-d'),
                'expiry_reason' => 'cancelled',
            ],
        ];
        yield [
            'P-3456-3456-3456-34',
            [
                'lpa'           => 'M-7890-0400-4003',
                'expiry_date'   => (new DateTimeImmutable())
                    ->add(new DateInterval('P1Y'))
                    ->format('Y-m-d'),
                'expiry_reason' => 'first_time_use',
            ],
        ];
    }

    #[Test]
    public function it_expires_provided_codes(): void
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);
        $responseProphecy->getBody()->willReturn(json_encode(['expiry_date' => '2025-10-24']));
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        $this->generatePSR17Prophecies(
            $responseProphecy->reveal(),
            'test-trace-id',
            [
                'code'          => 'P-1234-1234-1234-12',
                'expiry_reason' => 'paper_to_digital',
            ]
        );

        $this->requestFactoryProphecy
            ->createRequest(
                'POST',
                Argument::containingString('localhost/v1/paper-verification-code/expire'),
                Argument::any()
            )
            ->willReturn($this->requestProphecy->reveal());

        $sut = new PaperVerificationCodes(
            $this->httpClientProphecy->reveal(),
            $this->requestFactoryProphecy->reveal(),
            $this->streamFactoryProphecy->reveal(),
            $this->requestSignerFactoryProphecy->reveal(),
            'localhost',
            'test-trace-id',
        );

        $data = $sut->expire(
            new PaperVerificationCode('P-1234-1234-1234-12'),
            VerificationCodeExpiryReason::PAPER_TO_DIGITAL
        )->getData();

        $this->assertInstanceOf(PaperVerificationCodeExpiry::class, $data);
        $this->assertEquals(
            (new DateTimeImmutable('2025-10-24'))->setTime(0, 0),
            $data->expiresAt
        );
    }
}
