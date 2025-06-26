<?php

declare(strict_types=1);

namespace AppTest\Service\PaperVerificationCodes;

use App\DataAccess\Repository\ActorCodesInterface;
use App\Enum\LpaSource;
use App\Enum\LpaStatus;
use App\Enum\LpaType;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Request\PaperVerificationCodeUsable;
use App\Service\Lpa\Combined\RejectInvalidLpa;
use App\Service\Lpa\LpaManagerInterface;
use App\Service\PaperVerificationCodes\PaperVerificationCodeService;
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
    public function it_successfully_checks_a_code_for_usability(): void
    {
        $actorCodes       = $this->createMock(ActorCodesInterface::class);
        $lpaManager       = $this->createMock(LpaManagerInterface::class);
        $rejectInvalidLpa = $this->createMock(RejectInvalidLpa::class);

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-789Q-P4DF-4UX3', originator: 'P-1234-1234-1234-12')
            ->willReturn(LpaUtilities::lpaStoreResponseFixture());

        $sut = new PaperVerificationCodeService($actorCodes, $lpaManager, $rejectInvalidLpa);

        $params = new PaperVerificationCodeUsable(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
        );

        $result = $sut->usable($params);

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
    public function it_throws_an_exception_for_a_non_existent_code(): void
    {
        $actorCodes       = $this->createMock(ActorCodesInterface::class);
        $lpaManager       = $this->createMock(LpaManagerInterface::class);
        $rejectInvalidLpa = $this->createMock(RejectInvalidLpa::class);

        $sut = new PaperVerificationCodeService($actorCodes, $lpaManager, $rejectInvalidLpa);

        $params = new PaperVerificationCodeUsable(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-0123-0123-0123-01'),
        );

        $this->expectException(NotFoundException::class);
        $sut->usable($params);
    }

    #[Test]
    public function it_throws_an_exceptions_for_an_expired_lpa(): void
    {
        $params = new PaperVerificationCodeUsable(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-5678-5678-5678-56'),
        );

        $actorCodes       = $this->createMock(ActorCodesInterface::class);
        $lpaManager       = $this->createMock(LpaManagerInterface::class);
        $rejectInvalidLpa = $this->createMock(RejectInvalidLpa::class);

        $lpa = LpaUtilities::lpaStoreResponseFixture();
        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-789Q-P4DF-4UX3', originator: (string) $params->code)
            ->willReturn($lpa);

        $rejectInvalidLpa
            ->expects($this->once())
            ->method('__invoke')
            ->with($lpa, (string) $params->code, $params->name, $this->isType('array'))
            ->willThrowException(new GoneException('Share code expired'));

        $sut = new PaperVerificationCodeService($actorCodes, $lpaManager, $rejectInvalidLpa);

        $this->expectException(GoneException::class);
        $this->expectExceptionMessage('Share code expired');
        $sut->usable($params);
    }

    #[Test]
    public function it_throws_an_exceptions_for_a_cancelled_lpa(): void
    {
        $params = new PaperVerificationCodeUsable(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-5678-5678-5678-56'),
        );

        $actorCodes       = $this->createMock(ActorCodesInterface::class);
        $lpaManager       = $this->createMock(LpaManagerInterface::class);
        $rejectInvalidLpa = $this->createMock(RejectInvalidLpa::class);

        $lpa = LpaUtilities::lpaStoreResponseFixture();
        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-789Q-P4DF-4UX3', originator: (string) $params->code)
            ->willReturn($lpa);

        $rejectInvalidLpa
            ->expects($this->once())
            ->method('__invoke')
            ->with($lpa, (string) $params->code, $params->name, $this->isType('array'))
            ->willThrowException(new GoneException('Share code cancelled'));

        $sut = new PaperVerificationCodeService($actorCodes, $lpaManager, $rejectInvalidLpa);

        $this->expectException(GoneException::class);
        $this->expectExceptionMessage('Share code cancelled');
        $sut->usable($params);
    }

    #[Test]
    public function it_throws_an_exception_for_a_missing_lpa(): void
    {
        $actorCodes       = $this->createMock(ActorCodesInterface::class);
        $lpaManager       = $this->createMock(LpaManagerInterface::class);
        $rejectInvalidLpa = $this->createMock(RejectInvalidLpa::class);

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-789Q-P4DF-4UX3', originator: 'P-1234-1234-1234-12')
            ->willReturn(null);

        $sut = new PaperVerificationCodeService($actorCodes, $lpaManager, $rejectInvalidLpa);

        $params = new PaperVerificationCodeUsable(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
        );

        $this->expectException(NotFoundException::class);
        $sut->usable($params);
    }
}
