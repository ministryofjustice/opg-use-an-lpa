<?php

declare(strict_types=1);

namespace AppTest\Service\PaperVerificationCodes;

use App\Enum\LpaSource;
use App\Enum\LpaType;
use App\Exception\NotFoundException;
use App\Request\PaperVerificationCodeValidate;
use App\Service\Lpa\IsValid\LpaStatus;
use App\Service\Lpa\LpaManagerInterface;
use App\Service\PaperVerificationCodes\PaperVerificationCodeService;
use App\Value\LpaUid;
use App\Value\PaperVerificationCode;
use AppTest\LpaUtilities;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaperVerificationCodeService::class)]
class PaperVerificationCodeServiceTest extends TestCase
{
    #[Test]
    public function it_successfully_validates(): void
    {
        $lpaManager = $this->createMock(LpaManagerInterface::class);

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-789Q-P4DF-4UX3', originator: 'P-1234-1234-1234-12')
            ->willReturn(LpaUtilities::lpaStoreResponseFixture());

        $sut = new PaperVerificationCodeService($lpaManager);

        $params = new PaperVerificationCodeValidate(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
            lpaUid: new LpaUid('M-1111-2222-3333'),
            sentToDonor: false,
            attorneyName: 'Michael Clarkson',
            dateOfBirth: new DateTimeImmutable('2020-01-01'),
            noOfAttorneys: 2,
        );

        $result = $sut->validate($params);

        $this->assertEquals('Feeg Bundlaaaa', $result->donorName);
        $this->assertEquals(LpaType::PERSONAL_WELFARE, $result->lpaType);
        $this->assertEqualsWithDelta(
            (new DateTimeImmutable())->add(new DateInterval('P1Y')),
            $result->codeExpiryDate,
            5,
        );
        $this->assertEquals(LpaStatus::REGISTERED, $result->lpaStatus);
        $this->assertEquals(LpaSource::LPASTORE, $result->lpaSource);
    }

    #[Test]
    public function validation_throws_an_exception_for_a_missing_lpa(): void
    {
        $lpaManager = $this->createMock(LpaManagerInterface::class);

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-789Q-P4DF-4UX3', originator: 'P-1234-1234-1234-12')
            ->willReturn(null);

        $sut = new PaperVerificationCodeService($lpaManager);

        $params = new PaperVerificationCodeValidate(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
            lpaUid: new LpaUid('M-1111-2222-3333'),
            sentToDonor: false,
            attorneyName: 'Michael Clarkson',
            dateOfBirth: new DateTimeImmutable('2020-01-01'),
            noOfAttorneys: 2,
        );

        $this->expectException(NotFoundException::class);
        $sut->validate($params);
    }
}
