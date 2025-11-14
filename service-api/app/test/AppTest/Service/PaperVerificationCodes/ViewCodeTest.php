<?php

declare(strict_types=1);

namespace AppTest\Service\PaperVerificationCodes;

use App\Enum\LpaSource;
use App\Service\PaperVerificationCodes\CodeView;
use AppTest\LpaUtilities;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CodeView::class)]
class ViewCodeTest extends TestCase
{
    private function makeSut(): array
    {
        $lpa      = LpaUtilities::lpaStoreLpaFixture();
        $sut      = new CodeView(
            lpaSource: LpaSource::LPASTORE,
            lpa:       $lpa
        );
        $expected = [
            'source' => LpaSource::LPASTORE,
            'lpa'    => $lpa,
        ];
        return [$sut, $expected];
    }

    #[Test]
    public function test_properties_are_assigned(): void
    {
        [$sut, $expected] = $this->makeSut();
        $this->assertSame(LpaSource::LPASTORE, $sut->lpaSource);
        $this->assertSame($expected['lpa'], $sut->lpa);
    }

    #[Test]
    public function it_serialises_as_expected(): void
    {
        [$sut, $expected] = $this->makeSut();
        $this->assertSame($expected, $sut->jsonSerialize());
    }
}
