<?php

declare(strict_types=1);

namespace AppTest\Service\PaperVerificationCodes;

use App\DataAccess\Repository\PaperVerificationCodesInterface;
use App\DataAccess\Repository\Response\PaperVerificationCode as CodeResponse;
use App\Enum\LpaSource;
use App\Enum\LpaStatus;
use App\Enum\LpaType;
use App\Exception\BadRequestException;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Request\PaperVerificationCodeUsable;
use App\Request\PaperVerificationCodeValidate;
use App\Request\PaperVerificationCodeView;
use App\Service\Lpa\Combined\RejectInvalidLpa;
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
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(PaperVerificationCodeService::class)]
class PaperVerificationCodeServiceTest extends TestCase
{
    #[Test]
    public function it_successfully_checks_a_code_for_usability(): void
    {
        $params = new PaperVerificationCodeUsable(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
        );

        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willReturn(
                LpaUtilities::codesApiResponseFixture(
                    new CodeResponse(
                        lpaUid:    new LpaUid('M-789Q-P4DF-4UX3'),
                        cancelled: false,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-789Q-P4DF-4UX3', originator: (string) $params->code)
            ->willReturn(LpaUtilities::lpaStoreResponseFixture());

        $now = new DateTimeImmutable();
        $clock
            ->expects($this->any())
            ->method('now')
            ->willReturn($now);

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $result = $sut->usable($params);

        $this->assertEquals('Feeg Bundlaaaa', $result->donorName);
        $this->assertEquals(LpaType::PERSONAL_WELFARE, $result->lpaType);
        $this->assertEqualsWithDelta($now->add(new DateInterval('P1Y')), $result->expiresAt, 1);
        $this->assertEquals(LpaStatus::REGISTERED, $result->lpaStatus);
        $this->assertEquals(LpaSource::LPASTORE, $result->lpaSource);
    }

    #[Test]
    public function it_throws_an_exception_for_a_non_existent_code(): void
    {
        $params = new PaperVerificationCodeUsable(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-0123-0123-0123-01'),
        );

        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willThrowException(new NotFoundException());

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

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

        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willReturn(
                LpaUtilities::codesApiResponseFixture(
                    new CodeResponse(
                        lpaUid:    new LpaUid('M-789Q-P4DF-4UX3'),
                        cancelled: false,
                        expiresAt: (new DateTimeImmutable())->sub(new DateInterval('P1Y')),
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-789Q-P4DF-4UX3', originator: (string) $params->code)
            ->willReturn(LpaUtilities::lpaStoreResponseFixture());

        $clock
            ->expects($this->any())
            ->method('now')
            ->willReturn(new DateTimeImmutable());

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $this->expectException(GoneException::class);
        $this->expectExceptionMessage('Paper verification code expired');
        $sut->usable($params);
    }

    #[Test]
    public function it_throws_an_exceptions_for_a_cancelled_lpa(): void
    {
        $params = new PaperVerificationCodeUsable(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-3456-3456-3456-34'),
        );

        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willReturn(
                LpaUtilities::codesApiResponseFixture(
                    new CodeResponse(
                        lpaUid:    new LpaUid('M-789Q-P4DF-4UX3'),
                        cancelled: true,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-789Q-P4DF-4UX3', originator: (string) $params->code)
            ->willReturn(LpaUtilities::lpaStoreResponseFixture());

        $clock
            ->expects($this->any())
            ->method('now')
            ->willReturn(new DateTimeImmutable());

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $this->expectException(GoneException::class);
        $this->expectExceptionMessage('Paper verification code cancelled');
        $sut->usable($params);
    }

    #[Test]
    public function it_throws_an_exceptions_for_mismatched_donor_names(): void
    {
        $params = new PaperVerificationCodeUsable(
            name: 'Bundlishious',
            code: new PaperVerificationCode('P-3456-3456-3456-34'),
        );

        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willReturn(
                LpaUtilities::codesApiResponseFixture(
                    new CodeResponse(
                        lpaUid:    new LpaUid('M-789Q-P4DF-4UX3'),
                        cancelled: false,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-789Q-P4DF-4UX3', originator: (string) $params->code)
            ->willReturn(LpaUtilities::lpaStoreResponseFixture());

        $clock
            ->expects($this->any())
            ->method('now')
            ->willReturn(new DateTimeImmutable());

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $this->expectException(NotFoundException::class);
        $sut->usable($params);
    }

    #[Test]
    public function it_throws_an_exception_for_a_missing_lpa(): void
    {
        $params = new PaperVerificationCodeUsable(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
        );

        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willReturn(
                LpaUtilities::codesApiResponseFixture(
                    new CodeResponse(
                        lpaUid:    new LpaUid('M-789Q-P4DF-4UX3'),
                        cancelled: false,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-789Q-P4DF-4UX3', originator: $params->code)
            ->willReturn(null);

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $this->expectException(NotFoundException::class);
        $sut->usable($params);
    }

    #[Test]
    public function it_successfully_validates(): void
    {
        $params = new PaperVerificationCodeValidate(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
            lpaUid: new LpaUid('M-789Q-P4DF-4UX3'),
            sentToDonor: false,
            attorneyName: 'Michael Clarkson',
            dateOfBirth: new DateTimeImmutable('2020-01-01'),
            noOfAttorneys: 2,
        );

        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willReturn(
                LpaUtilities::codesApiResponseFixture(
                    new CodeResponse(
                        lpaUid:    $params->lpaUid,
                        cancelled: false,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with((string) $params->lpaUid, originator: (string) $params->code)
            ->willReturn(LpaUtilities::lpaStoreResponseFixture());

        $now = new DateTimeImmutable();
        $clock
            ->expects($this->any())
            ->method('now')
            ->willReturn($now);

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $result = $sut->validate($params);

        $this->assertEquals('Feeg Bundlaaaa', $result->donorName);
        $this->assertEquals(LpaType::PERSONAL_WELFARE, $result->lpaType);
        $this->assertEqualsWithDelta($now->add(new DateInterval('P1Y')), $result->expiresAt, 1);
        $this->assertEquals(LpaStatus::REGISTERED, $result->lpaStatus);
        $this->assertEquals(LpaSource::LPASTORE, $result->lpaSource);
    }

    #[Test]
    public function validation_throws_an_exception_for_a_missing_lpa(): void
    {
        $params = new PaperVerificationCodeValidate(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
            lpaUid: new LpaUid('M-789Q-P4DF-4UX3'),
            sentToDonor: false,
            attorneyName: 'Michael Clarkson',
            dateOfBirth: new DateTimeImmutable('2020-01-01'),
            noOfAttorneys: 2,
        );

        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willReturn(
                LpaUtilities::codesApiResponseFixture(
                    new CodeResponse(
                        lpaUid:    new LpaUid('M-789Q-P4DF-4UX3'),
                        cancelled: false,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-789Q-P4DF-4UX3', originator: $params->code)
            ->willReturn(null);

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $this->expectException(NotFoundException::class);
        $sut->validate($params);
    }

    #[Test]
    public function validation_throws_if_uid_is_unknown(): void
    {
        $params = new PaperVerificationCodeValidate(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
            lpaUid: new LpaUid('M-1111-1111-1111'),
            sentToDonor: false,
            attorneyName: 'Michael Clarkson',
            dateOfBirth: new DateTimeImmutable('2020-01-01'),
            noOfAttorneys: 2,
        );

        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willReturn(
                LpaUtilities::codesApiResponseFixture(
                    new CodeResponse(
                        lpaUid:    new LpaUid('M-789Q-P4DF-4UX3'),
                        cancelled: false,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-789Q-P4DF-4UX3', originator: $params->code)
            ->willReturn(null);

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $this->expectException(NotFoundException::class);
        $sut->validate($params);
    }

    #[Test]
    public function it_successfully_view(): void
    {
        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);
        $lpa        = LpaUtilities::lpaStoreLpaFixture();

        $params = new PaperVerificationCodeView(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
            lpaUid: new LpaUid('M-1111-2222-3333'),
            sentToDonor: false,
            attorneyName: 'Michael Clarkson',
            dateOfBirth: new DateTimeImmutable('2020-01-01'),
            noOfAttorneys: 2,
            organisation: 'Company A'
        );

        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willReturn(
                LpaUtilities::codesApiResponseFixture(
                    new CodeResponse(
                        lpaUid:    $params->lpaUid,
                        cancelled: false,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with($params->lpaUid, originator: $params->code)
            ->willReturn(LpaUtilities::lpaStoreResponseFixture());

        $clock
            ->expects($this->any())
            ->method('now')
            ->willReturn(new DateTimeImmutable());
        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $result = $sut->view($params);

        $this->assertEquals('Feeg Bundlaaaa', $result->donorName);
        $this->assertEquals(LpaType::PERSONAL_WELFARE, $result->lpaType);
        $this->assertEqualsWithDelta(
            (new DateTimeImmutable())->add(new DateInterval('P1Y')),
            $result->codeExpiryDate,
            5,
        );
        $this->assertEquals(LpaStatus::REGISTERED, $result->lpaStatus);
        $this->assertEquals(LpaSource::LPASTORE, $result->lpaSource);
        $this->assertEquals($lpa, $result->lpa);
    }

    #[Test]
    public function view_throws_an_exception_for_a_missing_lpa(): void
    {
        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $params = new PaperVerificationCodeView(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
            lpaUid: new LpaUid('M-789Q-P4DF-4UX3'),
            sentToDonor: false,
            attorneyName: 'Michael Clarkson',
            dateOfBirth: new DateTimeImmutable('2020-01-01'),
            noOfAttorneys: 2,
            organisation: 'Company A'
        );

        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willReturn(
                LpaUtilities::codesApiResponseFixture(
                    new CodeResponse(
                        lpaUid:    $params->lpaUid,
                        cancelled: false,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with($params->lpaUid, originator: $params->code)
            ->willReturn(null);

        $clock
            ->expects($this->any())
            ->method('now')
            ->willReturn(new DateTimeImmutable());

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $this->expectException(NotFoundException::class);
        $sut->view($params);
    }

    #[Test]
    public function view_throws_if_uid_is_unknown(): void
    {
        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $params = new PaperVerificationCodeView(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
            lpaUid: new LpaUid('M-1111-1111-1111'),
            sentToDonor: false,
            attorneyName: 'Michael Clarkson',
            dateOfBirth: new DateTimeImmutable('2020-01-01'),
            noOfAttorneys: 2,
            organisation: 'Company A'
        );

        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willReturn(
                LpaUtilities::codesApiResponseFixture(
                    new CodeResponse(
                        lpaUid:    $params->lpaUid,
                        cancelled: false,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                    )
                )
            );

        $lpaManager
            ->expects($this->any())
            ->method('getByUid')
            ->with($params->lpaUid, originator: $params->code)
            ->willReturn(null);

        $clock
            ->expects($this->any())
            ->method('now')
            ->willReturn(new DateTimeImmutable());

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $this->expectException(NotFoundException::class);
        $sut->view($params);
    }

    #[Test]
    public function view_throws_if_organisation_is_unknown(): void
    {
        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $params = new PaperVerificationCodeView(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
            lpaUid: new LpaUid('M-789Q-P4DF-4UX3'),
            sentToDonor: false,
            attorneyName: 'Michael Clarkson',
            dateOfBirth: new DateTimeImmutable('2020-01-01'),
            noOfAttorneys: 2,
            organisation: ''
        );

        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willReturn(
                LpaUtilities::codesApiResponseFixture(
                    new CodeResponse(
                        lpaUid:    $params->lpaUid,
                        cancelled: false,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with($params->lpaUid, originator: $params->code)
            ->willReturn(LpaUtilities::lpaStoreResponseFixture());

        $clock
            ->expects($this->any())
            ->method('now')
            ->willReturn(new DateTimeImmutable());

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $this->expectException(BadRequestException::class);
        $sut->view($params);
    }
}
