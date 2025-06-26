<?php

declare(strict_types=1);

namespace App\Service\PaperVerificationCodes;

use App\Enum\LpaSource;
use App\Enum\LpaStatus;
use App\Enum\LpaType;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\Assert;
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
            lpaType: LpaType::PERSONAL_WELFARE,
            codeExpiryDate: (new DateTimeImmutable())->add(new DateInterval('P1D')),
            lpaStatus:  LpaStatus::REGISTERED,
            lpaSource:  LpaSource::LPASTORE,
        );
    }

    #[Test]
    #[Depends('it_is_initializable')]
    public function it_serialises_as_expected(CodeUsable $sut): void
    {
        $json = json_encode($sut);

        Assert::assertJson($json);

        $obj = json_decode($json);
        Assert::assertObjectHasProperty('donorName', $obj);
        Assert::assertObjectHasProperty('type', $obj);
        Assert::assertObjectHasProperty('expiryDate', $obj);
        Assert::assertObjectHasProperty('status', $obj);
        Assert::assertObjectHasProperty('source', $obj);
    }
}
