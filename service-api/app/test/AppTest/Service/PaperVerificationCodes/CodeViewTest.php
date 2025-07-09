<?php

declare(strict_types=1);

namespace AppTest\Service\PaperVerificationCodes;

use App\Enum\LpaSource;
use App\Enum\LpaStatus;
use App\Enum\LpaType;
use App\Service\PaperVerificationCodes\ViewCode;
use AppTest\LpaUtilities;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ViewCode::class)]
class CodeViewTest extends TestCase
{
    private function makeSut(): array
    {
        $expiry = (new DateTimeImmutable())->add(new DateInterval('P1Y'));
        $lpa    = LpaUtilities::lpaStoreLpaFixture();

        $sut = new ViewCode(
            donorName: 'Barbara Gilson',
            lpaType: LpaType::PERSONAL_WELFARE,
            codeExpiryDate: $expiry,
            lpaStatus:  LpaStatus::REGISTERED,
            lpaSource:  LpaSource::LPASTORE,
            lpa:        $lpa
        );

        $expected = [
            'donorName'  => 'Barbara Gilson',
            'type'       => LpaType::PERSONAL_WELFARE,
            'expiryDate' => $expiry->format(DateTimeInterface::ATOM),
            'status'     => LpaStatus::REGISTERED,
            'source'     => LpaSource::LPASTORE,
            'lpa'        => $lpa,
        ];

        return [$sut, $expected];
    }

    #[Test]
    public function test_properties_are_assigned(): void
    {
        [$sut] = $this->makeSut();

        self::assertSame('Barbara Gilson', $sut->donorName);
        self::assertSame(LpaType::PERSONAL_WELFARE, $sut->lpaType);
        self::assertSame(LpaStatus::REGISTERED, $sut->lpaStatus);
        self::assertSame(LpaSource::LPASTORE, $sut->lpaSource);
    }

    #[Test]
    public function it_serialises_as_expected(): void
    {
        [$sut, $expected] = $this->makeSut();

        self::assertSame($expected, $sut->jsonSerialize());
    }
}