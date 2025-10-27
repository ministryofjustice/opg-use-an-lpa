<?php

declare(strict_types=1);

namespace AppTest\Service\PaperVerificationCodes;

use App\DataAccess\Repository\PaperVerificationCodesInterface;
use App\DataAccess\Repository\Response\PaperVerificationCode as CodeDTO;
use App\Enum\LpaSource;
use App\Enum\LpaStatus;
use App\Enum\LpaType;
use App\Exception\BadRequestException;
use App\Enum\VerificationCodeExpiryReason;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Request\PaperVerificationCodeUsable;
use App\Request\PaperVerificationCodeValidate;
use App\Request\PaperVerificationCodeView;
use App\Service\Lpa\LpaManagerInterface;
use App\Service\PaperVerificationCodes\PaperVerificationCodeService;
use App\Value\LpaUid;
use App\Value\PaperVerificationCode;
use AppTest\LpaUtilities;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
                    new CodeDTO(
                        lpaUid:    new LpaUid('M-7890-0400-4003'),
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                        expiryReason: VerificationCodeExpiryReason::FIRST_TIME_USE,
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-7890-0400-4003', originator: (string) $params->code)
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
                    new CodeDTO(
                        lpaUid:    new LpaUid('M-7890-0400-4003'),
                        expiresAt: (new DateTimeImmutable())->sub(new DateInterval('P1Y')),
                        expiryReason: VerificationCodeExpiryReason::FIRST_TIME_USE,
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-7890-0400-4003', originator: (string) $params->code)
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
                    new CodeDTO(
                        lpaUid:    new LpaUid('M-7890-0400-4003'),
                        // TODO UML-4080 add below needs changing to sub  , to work for cancelled codes that have also have expiry date in the past
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                        expiryReason: VerificationCodeExpiryReason::CANCELLED
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-7890-0400-4003', originator: (string) $params->code)
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
                    new CodeDTO(
                        lpaUid:    new LpaUid('M-7890-0400-4003'),
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                        expiryReason: VerificationCodeExpiryReason::FIRST_TIME_USE,
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-7890-0400-4003', originator: (string) $params->code)
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
                    new CodeDTO(
                        lpaUid:    new LpaUid('M-7890-0400-4003'),
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                        expiryReason: VerificationCodeExpiryReason::FIRST_TIME_USE,
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-7890-0400-4003', originator: $params->code)
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
            lpaUid: new LpaUid('M-7890-0400-4003'),
            sentToDonor: false,
            attorneyName: 'Herman Seakrest',
            dateOfBirth: new DateTimeImmutable('1982-07-24'),
            noOfAttorneys: 1,
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
                    new CodeDTO(
                        lpaUid:    $params->lpaUid,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                        expiryReason: VerificationCodeExpiryReason::FIRST_TIME_USE,
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
        $this->assertEqualsWithDelta($now->add(new DateInterval('P1Y')), $result->expiresAt, 3);
        $this->assertEquals(LpaStatus::REGISTERED, $result->lpaStatus);
        $this->assertEquals(LpaSource::LPASTORE, $result->lpaSource);
    }

    #[Test]
    public function validation_throws_an_exception_for_a_missing_lpa(): void
    {
        $params = new PaperVerificationCodeValidate(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
            lpaUid: new LpaUid('M-7890-0400-4003'),
            sentToDonor: false,
            attorneyName: 'Herman Seakrest',
            dateOfBirth: new DateTimeImmutable('2020-01-01'),
            noOfAttorneys: 1,
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
                    new CodeDTO(
                        lpaUid:    new LpaUid('M-7890-0400-4003'),
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                        expiryReason: VerificationCodeExpiryReason::FIRST_TIME_USE,
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with('M-7890-0400-4003', originator: $params->code)
            ->willReturn(null);

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $this->expectException(NotFoundException::class);
        $sut->validate($params);
    }

    #[Test]
    #[DataProvider('validationDataProvider')]
    public function validation_throws_if_param_does_not_match(PaperVerificationCodeValidate $params): void
    {
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
                    new CodeDTO(
                        lpaUid:    new LpaUid('M-7890-0400-4003'),
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                        expiryReason: VerificationCodeExpiryReason::FIRST_TIME_USE,
                    )
                )
            );

        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with(uid: 'M-7890-0400-4003', originator: (string) $params->code)
            ->willReturn(LpaUtilities::lpaStoreResponseFixture());

        $now = new DateTimeImmutable();
        $clock
            ->expects($this->any())
            ->method('now')
            ->willReturn($now);

        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);

        $this->expectException(NotFoundException::class);
        $sut->validate($params);
    }

    public static function validationDataProvider(): array
    {
        return [
            'uid_is_incorrect'            => [
                new PaperVerificationCodeValidate(
                    name: 'Bundlaaaa',
                    code: new PaperVerificationCode('P-1234-1234-1234-12'),
                    lpaUid: new LpaUid('M-1111-1111-1111'),
                    sentToDonor: false,
                    attorneyName: 'Herman Seakrest',
                    dateOfBirth: new DateTimeImmutable('1982-07-24'),
                    noOfAttorneys: 1,
                ),
            ],
            'name_is_incorrect'           => [
                new PaperVerificationCodeValidate(
                    name: 'Bundlaaaa',
                    code: new PaperVerificationCode('P-1234-1234-1234-12'),
                    lpaUid: new LpaUid('M-7890-0400-4003'),
                    sentToDonor: false,
                    attorneyName: 'Steven Alexander Miller',
                    dateOfBirth: new DateTimeImmutable('1982-07-24'),
                    noOfAttorneys: 1,
                ),
            ],
            'attorney_dob_is_incorrect'   => [
                new PaperVerificationCodeValidate(
                    name: 'Bundlaaaa',
                    code: new PaperVerificationCode('P-1234-1234-1234-12'),
                    lpaUid: new LpaUid('M-7890-0400-4003'),
                    sentToDonor: false,
                    attorneyName: 'Herman Seakrest',
                    dateOfBirth: new DateTimeImmutable('1970-03-12'),
                    noOfAttorneys: 1,
                ),
            ],
            'donor_dob_is_incorrect'      => [
                new PaperVerificationCodeValidate(
                    name: 'Bundlaaaa',
                    code: new PaperVerificationCode('P-1234-1234-1234-12'),
                    lpaUid: new LpaUid('M-7890-0400-4003'),
                    sentToDonor: true,
                    attorneyName: 'Herman Seakrest',
                    dateOfBirth: new DateTimeImmutable('1983-06-19'),
                    noOfAttorneys: 1,
                ),
            ],
            'attorney_count_is_incorrect' => [
                new PaperVerificationCodeValidate(
                    name: 'Bundlaaaa',
                    code: new PaperVerificationCode('P-1234-1234-1234-12'),
                    lpaUid: new LpaUid('M-7890-0400-4003'),
                    sentToDonor: false,
                    attorneyName: 'Herman Seakrest',
                    dateOfBirth: new DateTimeImmutable('1982-07-24'),
                    noOfAttorneys: 3,
                ),
            ],
        ];
    }

    #[Test]
    public function it_successfully_view(): void
    {
        $params = new PaperVerificationCodeView(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
            lpaUid: new LpaUid('M-7890-0400-4003'),
            sentToDonor: true,
            attorneyName: 'Herman Seakrest',
            dateOfBirth: new DateTimeImmutable('1970-01-24'),
            noOfAttorneys: 1,
            organisation: 'Company A'
        );

        $paperCodes = $this->createMock(PaperVerificationCodesInterface::class);
        $lpaManager = $this->createMock(LpaManagerInterface::class);
        $clock      = $this->createMock(ClockInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);
        $lpa        = LpaUtilities::lpaStoreLpaFixture();
 
        $paperCodes
            ->expects($this->once())
            ->method('validate')
            ->with($params->code)
            ->willReturn(
                LpaUtilities::codesApiResponseFixture(
                    new CodeDTO(
                        lpaUid:    $params->lpaUid,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                        expiryReason: null,
                    )
                )
            );
        
        $lpaManager
            ->expects($this->once())
            ->method('getByUid')
            ->with($params->lpaUid, originator: $params->code)
            ->willReturn(LpaUtilities::lpaStoreResponseFixture());

        $now = new DateTimeImmutable();
        $clock
            ->expects($this->any())
            ->method('now')
            ->willReturn($now);
        
        $sut = new PaperVerificationCodeService($paperCodes, $lpaManager, $clock, $logger);
        
        $result = $sut->view($params);

        $this->assertEquals(LpaSource::LPASTORE, $result->lpaSource);
        $this->assertEquals($lpa, $result->lpa);
    }

    #[Test]
    public function view_throws_an_exception_for_a_missing_lpa(): void
    {
        $params = new PaperVerificationCodeView(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
            lpaUid: new LpaUid('M-7890-0400-4003'),
            sentToDonor: true,
            attorneyName: 'Herman Seakrest',
            dateOfBirth: new DateTimeImmutable('1970-01-24'),
            noOfAttorneys: 1,
            organisation: 'Company A'
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
                    new CodeDTO(
                        lpaUid:    $params->lpaUid,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                        expiryReason: null,
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
        $params = new PaperVerificationCodeView(
            name: 'Bundlaaaa',
            code: new PaperVerificationCode('P-1234-1234-1234-12'),
            lpaUid: new LpaUid('M-1111-1111-1111'),
            sentToDonor: true,
            attorneyName: 'Herman Seakrest',
            dateOfBirth: new DateTimeImmutable('1970-01-24'),
            noOfAttorneys: 1,
            organisation: 'Company A'
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
                    new CodeDTO(
                        lpaUid:    $params->lpaUid,
                        expiresAt: (new DateTimeImmutable())->add(new DateInterval('P1Y')),
                        expiryReason: null,
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
}
