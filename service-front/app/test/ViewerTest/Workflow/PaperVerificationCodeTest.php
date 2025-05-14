<?php

declare(strict_types=1);

namespace ViewerTest\Workflow;

use DateTimeInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Viewer\Workflow\PaperVerificationShareCode;

#[CoversClass(PaperVerificationShareCode::class)]
class PaperVerificationCodeTest extends TestCase
{
    #[Test]
    #[DoesNotPerformAssertions]
    public function it_can_be_created_empty(): void
    {
        new PaperVerificationShareCode();
    }

    #[Test]
    public function it_can_be_created_with_data(): void
    {
        $sut = new PaperVerificationShareCode(
            lastName:    'Tester',
            code:        'P-ADGH-ZXCV-BNTR-36',
            lpaUid:      'M-794D-TP43-XQ86',
            dateOfBirth: '1955-11-05',
        );

        Assert::assertEquals('Tester', $sut->lastName);
        Assert::assertEquals('P-ADGH-ZXCV-BNTR-36', $sut->code);
        Assert::assertEquals('M-794D-TP43-XQ86', $sut->lpaUid);
        Assert::assertInstanceOf(DateTimeInterface::class, $sut->dateOfBirth);
        Assert::assertEquals('1955-11-05T00:00:00+00:00', $sut->dateOfBirth->format('c'));
    }

    #[Test]
    public function it_can_be_reset(): void
    {
        $sut = new PaperVerificationShareCode(
            lastName:    'Tester',
            code:        'P-ADGH-ZXCV-BNTR-36',
            lpaUid:      'M-794D-TP43-XQ86',
            dateOfBirth: '1955-11-05',
        );

        $sut->reset();

        Assert::assertNull($sut->lastName);
        Assert::assertNull($sut->code);
        Assert::assertNull($sut->lpaUid);
        Assert::assertNull($sut->dateOfBirth);
    }
}
