<?php

declare(strict_types=1);

namespace AppTest\Service\PaperVerificationCodes;

use App\Enum\LpaSource;
use App\Enum\LpaType;
use App\Service\Lpa\IsValid\LpaStatus;
use App\Service\PaperVerificationCodes\CodeValidate;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CodeValidate::class)]
class CodeValidateTest extends TestCase
{
    #[Test]
    #[DoesNotPerformAssertions]
    public function it_is_initializable(): CodeValidate
    {
        return new CodeValidate(
            donorName: 'Barbara Gilson',
            lpaType: LpaType::PERSONAL_WELFARE,
            codeExpiryDate: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
            lpaStatus:  LpaStatus::REGISTERED,
            lpaSource:  LpaSource::LPASTORE,
        );
    }

    #[Test]
    #[Depends('it_is_initializable')]
    public function it_serialises_as_expected(CodeValidate $sut): void
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