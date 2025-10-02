<?php

declare(strict_types=1);

namespace AppTest\Service\PaperVerificationCodes;

use App\Enum\LpaSource;
use App\Enum\LpaStatus;
use App\Enum\LpaType;
use App\Enum\VerificationCodeExpiryReason;
use App\Service\PaperVerificationCodes\CodeUsable;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CodeUsable::class)]
class CodeUsableTest extends TestCase
{
    #[Test]
    #[DoesNotPerformAssertions]
    public function it_is_initializable(): CodeUsable
    {
        return new CodeUsable(
            donorName: 'Test Tester',
            lpaType:   LpaType::PERSONAL_WELFARE,
            lpaStatus: LpaStatus::REGISTERED,
            lpaSource: LpaSource::LPASTORE,
            expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1D')),
            expiryReason: VerificationCodeExpiryReason::FIRST_TIME_USE,
        );
    }

    #[Test]
    #[Depends('it_is_initializable')]
    public function it_serialises_as_expected(CodeUsable $sut): void
    {
        $json = json_encode($sut);

        $this->assertJson($json);

        $obj = json_decode($json);
        $this->assertObjectHasProperty('donorName', $obj);
        $this->assertObjectHasProperty('type', $obj);
        $this->assertObjectHasProperty('expiresAt', $obj);
        $this->assertObjectHasProperty('status', $obj);
        $this->assertObjectHasProperty('source', $obj);
        $this->assertObjectHasProperty('expiryReason', $obj);
    }
}
