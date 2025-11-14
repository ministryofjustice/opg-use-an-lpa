<?php

declare(strict_types=1);

namespace AppTest\Service\ActorCodes;

use App\DataAccess\Repository\Response\ActorCodeIsValid;
use App\DataAccess\Repository\Response\Lpa;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Entity\Sirius\SiriusLpa as CombinedSiriusLpa;
use App\Entity\Sirius\SiriusLpaAttorney;
use App\Exception\ActorCodeMarkAsUsedException;
use App\Exception\ActorCodeValidationException;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\ActorCodes\CodeValidationStrategyInterface;
use App\Service\ActorCodes\ValidatedActorCode;
use App\Service\Lpa\LpaManagerInterface;
use App\Service\Lpa\ResolveActor;
use App\Service\Lpa\ResolveActor\ActorType;
use App\Service\Lpa\ResolveActor\HasActorInterface;
use App\Service\Lpa\ResolveActor\LpaActor;
use App\Service\Lpa\SiriusLpa;
use App\Service\Lpa\SiriusPerson;
use BehatTest\LpaTestUtilities;
use DateInterval;
use DateTime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class ActorCodeServiceTest extends TestCase
{
    use ProphecyTrait;

    private CodeValidationStrategyInterface|ObjectProphecy $codeValidatorProphecy;
    private LpaManagerInterface|ObjectProphecy $lpaManagerProphecy;
    private string $testActorUid;
    private UserLpaActorMapInterface|ObjectProphecy $userLpaActorMapInterfaceProphecy;
    private LoggerInterface|ObjectProphecy $loggerProphecy;
    private ResolveActor|ObjectProphecy $resolveActorProphecy;

    public function setUp(): void
    {
        $this->codeValidatorProphecy            = $this->prophesize(CodeValidationStrategyInterface::class);
        $this->lpaManagerProphecy               = $this->prophesize(LpaManagerInterface::class);
        $this->userLpaActorMapInterfaceProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $this->loggerProphecy                   = $this->prophesize(LoggerInterface::class);
        $this->resolveActorProphecy             = $this->prophesize(ResolveActor::class);
    }

    #[Test]
    public function confirmation_fails_with_invalid_details(): void
    {
        $this->codeValidatorProphecy->validateCode('test-code', 'test-uid', '1982-10-28')
            ->shouldBeCalled()
            ->willThrow(new ActorCodeValidationException());

        $service = $this->getActorCodeService();

        $result = $service->confirmDetails('test-code', 'test-uid', '1982-10-28', 'test-user');

        $this->assertNull($result);
    }

    #[Test]
    public function confirmation_succeeds_with_valid_details(): void
    {
        $this->initValidParameterSet();

        $this->codeValidatorProphecy->flagCodeAsUsed('test-code')
            ->shouldBeCalled();

        $this->userLpaActorMapInterfaceProphecy->create(
            'test-user',
            'test-uid',
            $this->testActorUid,
            null,
            null,
            'test-code',
            true,
        )
            ->willReturn('00000000-0000-4000-A000-000000000000')
            ->shouldBeCalled();

        $this->userLpaActorMapInterfaceProphecy->getByUserId('test-user')->willReturn([])->shouldBeCalled();

        $service = $this->getActorCodeService();

        $result = $service->confirmDetails('test-code', 'test-uid', '1982-10-28', 'test-user');

        $this->assertSame('00000000-0000-4000-A000-000000000000', $result);
    }

    #[Test]
    public function confirmation_succeeds_with_valid_details_ttl_removed(): void
    {
        $this->initValidParameterSet();

        $this->codeValidatorProphecy->flagCodeAsUsed('test-code')
            ->shouldBeCalled();

        $this->userLpaActorMapInterfaceProphecy->activateRecord(
            'token-3',
            $this->testActorUid,
            'test-code',
            true,
        )->willReturn([]);

        $mapResults = [
            [
                'Id'             => 'token-3',
                'SiriusUid'      => 'test-uid',
                'ActorId'        => 3,
                'ActivateBy'     => (new DateTime('now'))->add(new DateInterval('P1Y'))->getTimeStamp(),
                'Added'          => new DateTime('now'),
                'ActivationCode' => 'test-code',
            ],
        ];

        $this->userLpaActorMapInterfaceProphecy->getByUserId('test-user')->willReturn($mapResults)->shouldBeCalled();

        $service = $this->getActorCodeService();

        $result = $service->confirmDetails('test-code', 'test-uid', '1982-10-28', 'test-user');

        // We expect a uuid4 back.
        $this->assertSame('token-3', $result);
    }

    #[Test]
    public function confirmation_with_valid_details_fails_flag_as_used(): void
    {
        $this->initValidParameterSet();

        $this->codeValidatorProphecy->flagCodeAsUsed('test-code')
            ->willThrow(new ActorCodeMarkAsUsedException());

        $this->userLpaActorMapInterfaceProphecy->create(
            'test-user',
            'test-uid',
            $this->testActorUid,
            null,
            null,
            'test-code',
            true,
        )->willReturn('00000000-0000-4000-A000-000000000000');

        $this->userLpaActorMapInterfaceProphecy->delete('00000000-0000-4000-A000-000000000000')
            ->shouldBeCalled();

        //---

        $service = $this->getActorCodeService();

        $this->userLpaActorMapInterfaceProphecy->getByUserId('test-user')->willReturn([])->shouldBeCalled();

        $result = $service->confirmDetails('test-code', 'test-uid', '1982-10-28', 'test-user');
    }

    #[Test]
    public function successful_validation(): void
    {
        [
            $testCode,
            $testUid,
            $testCombinedUid,
            $testDob,
            $testActorId,
            $testActorUid,
            $mockLpa,
            $mockCombinedLpa,
            $mockActor,
            $mockCombinedActor,
        ] = $this->initValidParameterSet();

        $service = $this->getActorCodeService();

        $result = $service->validateDetails($testCode, $testUid, $testDob);
        $this->assertInstanceOf(ValidatedActorCode::class, $result);

        $this->assertArrayHasKey('lpa', $result->jsonSerialize());
        $this->assertArrayHasKey('actor', $result->jsonSerialize());
        $this->assertEquals($mockLpa, $result->jsonSerialize()['lpa']);
        $this->assertEquals($mockActor, $result->jsonSerialize()['actor']);
    }

    #[Test]
    public function successful_validation_with_combined_format(): void
    {
        [
            $testCode,
            $testUid,
            $testCombinedUid,
            $testDob,
            $testActorId,
            $testActorUid,
            $mockLpa,
            $mockCombinedLpa,
            $mockActor,
            $mockCombinedActor,
        ] = $this->initValidParameterSet();

        $service = $this->getActorCodeService();

        $result = $service->validateDetails($testCode, $testCombinedUid, $testDob);
        $this->assertInstanceOf(ValidatedActorCode::class, $result);

        $this->assertArrayHasKey('lpa', $result->jsonSerialize());
        $this->assertArrayHasKey('actor', $result->jsonSerialize());
        $this->assertEquals($mockCombinedLpa, $result->jsonSerialize()['lpa']);
        $this->assertEquals($mockCombinedActor, $result->jsonSerialize()['actor']);
    }

    #[Test]
    public function validation_fails(): void
    {
        $testCode = 'test-code';
        $testUid  = 'test-uid';
        $testDob  = '1982-10-28';

        $this->codeValidatorProphecy->validateCode($testCode, $testUid, $testDob)
            ->willThrow(new ActorCodeValidationException())
            ->shouldBeCalled();

        $service = $this->getActorCodeService();

        $result = $service->validateDetails($testCode, $testUid, $testDob);

        $this->assertNotInstanceOf(ValidatedActorCode::class, $result);
    }

    private function getActorCodeService(): ActorCodeService
    {
        return new ActorCodeService(
            $this->codeValidatorProphecy->reveal(),
            $this->userLpaActorMapInterfaceProphecy->reveal(),
            $this->lpaManagerProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );
    }

    private function initValidParameterSet(): array
    {
        $testCode           = 'test-code';
        $testUid            = 'test-uid';
        $testCombinedUid    = 'test-combined-uid';
        $testDob            = '1982-10-28';
        $testActorId        = 1;
        $this->testActorUid = '123456789012';

        $mockLpa = new SiriusLpa(
            [
                'uId' => $testUid,
            ],
            $this->loggerProphecy->reveal(),
        );

        $mockCombinedLpa = LpaTestUtilities::MapEntityFromData(
            [
                'uId' => $testCombinedUid,
            ],
            CombinedSiriusLpa::class
        );

        $mockActor = new LpaActor(
            new SiriusPerson(
                [
                    'dob' => $testDob,
                    'id'  => $testActorId,
                    'uId' => $this->testActorUid,
                ],
                $this->loggerProphecy->reveal(),
            ),
            ActorType::ATTORNEY,
        );

        $mockCombinedActor = new LpaActor(
            LpaTestUtilities::MapEntityFromData(
                [
                    'dob' => $testDob,
                    'id'  => $testActorId,
                    'uId' => $this->testActorUid,
                ],
                SiriusLpaAttorney::class,
            ),
            ActorType::ATTORNEY,
        );

        $this->codeValidatorProphecy->validateCode($testCode, $testUid, $testDob)
            ->willReturn(new ActorCodeIsValid($this->testActorUid, true));
        $this->lpaManagerProphecy->getByUid($testUid)->willReturn(
            new Lpa($mockLpa, new DateTime())
        );

        $this->codeValidatorProphecy->validateCode($testCode, $testCombinedUid, $testDob)
            ->willReturn(new ActorCodeIsValid($this->testActorUid, null));
        $this->lpaManagerProphecy->getByUid($testCombinedUid)->willReturn(
            new Lpa($mockCombinedLpa, new DateTime())
        );

        $this->resolveActorProphecy
            ->__invoke(Argument::type(HasActorInterface::class), Argument::type('string'))
            ->will(function ($args) use ($mockActor, $mockCombinedActor) {
                if ($args[0] instanceof SiriusLpa) {
                    return $mockActor;
                } else {
                    return $mockCombinedActor;
                }
            })
            ->shouldBeCalled();

        return [
            $testCode,
            $testUid,
            $testCombinedUid,
            $testDob,
            $testActorId,
            $this->testActorUid,
            $mockLpa,
            $mockCombinedLpa,
            $mockActor,
            $mockCombinedActor,
        ];
    }
}
