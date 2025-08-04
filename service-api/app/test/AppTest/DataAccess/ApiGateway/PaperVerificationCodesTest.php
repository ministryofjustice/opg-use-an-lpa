<?php

declare(strict_types=1);

namespace AppTest\DataAccess\ApiGateway;

use App\DataAccess\ApiGateway\PaperVerificationCodes;
use App\DataAccess\ApiGateway\RequestSigner;
use App\DataAccess\ApiGateway\RequestSignerFactory;
use App\DataAccess\Repository\Response\PaperVerificationCode as PaperVerificationCodeResponse;
use App\Value\PaperVerificationCode;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Fig\Http\Message\StatusCodeInterface;
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

    /**
     * @param string $code
     * @param array{
     *      lpa: string,
     *      expires?: string,
     *      cancelled?: string
     * }  $response
     * @return void
     */
    #[Test]
    #[DataProvider('codeDataProvider')]
    public function it_fetches_codes_with_differing_upstream_response_fields(string $code, array $response): void
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getStatusCode()->willReturn(StatusCodeInterface::STATUS_OK);
        $responseProphecy->getBody()->willReturn(json_encode($response));
        $responseProphecy->getHeaderLine('Date')->willReturn('2020-04-04T13:30:00+00:00');

        // TODO once the code actually implements upstream API calls swap this for "generatePSR17Prophecies"
        $this->generatePSR17PropheciesWithoutAssertions(
            $responseProphecy->reveal(),
            'test-trace-id',
            [
                'code' => $code,
            ]
        );

        $this->requestFactoryProphecy
            ->createRequest(
                'POST',
                Argument::containingString('localhost/v1/paper-verification-codes/validate'),
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

        if (isset($response['expires'])) {
            $this->assertEqualsWithDelta(new DateTimeImmutable($response['expires']), $data->expiresAt, 5);
        }

        if (isset($response['cancelled'])) {
            $this->assertEquals($response['cancelled'], $data->cancelled);
        }
    }

    public static function codeDataProvider(): array
    {
        return [
            [
                'P-1234-1234-1234-12',
                [
                    'lpa' => 'M-789Q-P4DF-4UX3', // no expiry as it's not been used yet
                ],
            ],
            [
                'P-5678-5678-5678-56',
                [
                    'lpa'     => 'M-789Q-P4DF-4UX3',
                    'expires' => (new DateTimeImmutable())
                        ->sub(new DateInterval('P1Y')) // code has expired
                        ->format(DateTimeInterface::ATOM),
                ],
            ],
            [
                'P-3456-3456-3456-34',
                [
                    'lpa'       => 'M-789Q-P4DF-4UX3',
                    'expires'   => (new DateTimeImmutable())
                        ->add(new DateInterval('P1Y'))
                        ->format(DateTimeInterface::ATOM),
                    'cancelled' => 'true', // code valid but cancelled
                ],
            ],
        ];
    }
}
