<?php

declare(strict_types=1);

namespace AppTest\Service\ActorCodes;

use App\DataAccess\Repository;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ActorCodeMarkAsUsedException;
use App\Exception\ActorCodeValidationException;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\ActorCodes\CodeValidationStrategyInterface;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\ResolveActor;
use DateTime;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class ActorCodeServiceTest extends TestCase
{
    /** @var CodeValidationStrategyInterface|ObjectProphecy */
    private $codeValidatorProphecy;

    /** @var LpaService|ObjectProphecy */
    private $lpaServiceProphecy;
    private string $testActorUid;

    /** @var UserLpaActorMapInterface|ObjectProphecy */
    private $userLpaActorMapInterfaceProphecy;

    /** @var LoggerInterface|ObjectProphecy */
    private $loggerProphecy;

    /** @var ResolveActor|ObjectProphecy */
    private $resolveActorProphecy;

    public function setUp(): void
    {
        $this->codeValidatorProphecy = $this->prophesize(CodeValidationStrategyInterface::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->userLpaActorMapInterfaceProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->resolveActorProphecy = $this->prophesize(ResolveActor::class);
    }

    /** @test */
    public function confirmation_fails_with_invalid_details(): void
    {
        $this->codeValidatorProphecy->validateCode('test-code', 'test-uid', 'test-dob')
            ->shouldBeCalled()
            ->willThrow(new ActorCodeValidationException());

        $service = $this->getActorCodeService();

        $result = $service->confirmDetails('test-code', 'test-uid', 'test-dob', 'test-user');

        $this->assertNull($result);
    }

    /** @test */
    public function confirmation_succeeds_with_valid_details(): void
    {
        $this->initValidParameterSet();

        $this->codeValidatorProphecy->flagCodeAsUsed('test-code')
            ->willReturn('id-of-db-row')
            ->shouldBeCalled();

        $this->userLpaActorMapInterfaceProphecy->create(
            'test-user',
            'test-uid',
            $this->testActorUid,
            null,
            null,
            'test-code'
        )
            ->willReturn('00000000-0000-4000-A000-000000000000')
            ->shouldBeCalled();

        $this->userLpaActorMapInterfaceProphecy->getByUserId('test-user')->willReturn([])->shouldBeCalled();

        $service = $this->getActorCodeService();

        $result = $service->confirmDetails('test-code', 'test-uid', 'test-dob', 'test-user');

        $this->assertEquals('00000000-0000-4000-A000-000000000000', $result);
    }

    /** @test */
    public function confirmation_succeeds_with_valid_details_ttl_removed(): void
    {
        $this->initValidParameterSet();

        $this->codeValidatorProphecy->flagCodeAsUsed('test-code')
            ->willReturn('id-of-db-row')
            ->shouldBeCalled();

        $this->userLpaActorMapInterfaceProphecy->activateRecord(
            'token-3',
            $this->testActorUid,
            'test-code'
        )->shouldBeCalled();

        $mapResults = [
            [
                'Id' => 'token-3',
                'SiriusUid' => 'test-uid',
                'ActorId' => 3,
                'ActivateBy' => (new DateTime('now'))->add(new \DateInterval('P1Y'))->getTimeStamp(),
                'Added'   => new DateTime('now'),
                'ActivationCode' => 'test-code'
            ]
        ];

        $this->userLpaActorMapInterfaceProphecy->getByUserId('test-user')->willReturn($mapResults)->shouldBeCalled();

        $service = $this->getActorCodeService();

        $result = $service->confirmDetails('test-code', 'test-uid', 'test-dob', 'test-user');

        // We expect a uuid4 back.
        $this->assertEquals('token-3', $result);
    }

    /** @test */
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
            'test-code'
        )->willReturn('00000000-0000-4000-A000-000000000000');

        $this->userLpaActorMapInterfaceProphecy->delete('00000000-0000-4000-A000-000000000000')
            ->shouldBeCalled();

        //---

        $service = $this->getActorCodeService();

        $this->userLpaActorMapInterfaceProphecy->getByUserId('test-user')->willReturn([])->shouldBeCalled();

        $result = $service->confirmDetails('test-code', 'test-uid', 'test-dob', 'test-user');
    }

    /** @test */
    public function successful_validation(): void
    {
        [
            $testCode,
            $testUid,
            $testDob,
            $testActorId,
            $testActorUid,
            $mockLpa,
            $mockActor,
        ] = $this->initValidParameterSet();

        $service = $this->getActorCodeService();

        $result = $service->validateDetails($testCode, $testUid, $testDob);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('lpa', $result);
        $this->assertArrayHasKey('actor', $result);
        $this->assertEquals($mockLpa, $result['lpa']);
        $this->assertEquals($mockActor, $result['actor']);
    }

    /** @test */
    public function validation_fails(): void
    {
        $testCode     = 'test-code';
        $testUid      = 'test-uid';
        $testDob      = 'test-dob';

        $this->codeValidatorProphecy->validateCode($testCode, $testUid, $testDob)
            ->willThrow(new ActorCodeValidationException())
            ->shouldBeCalled();

        $service = $this->getActorCodeService();

        $result = $service->validateDetails($testCode, $testUid, $testDob);

        $this->assertNull($result);
    }

    private function getActorCodeService(): ActorCodeService
    {
        return new ActorCodeService(
            $this->codeValidatorProphecy->reveal(),
            $this->userLpaActorMapInterfaceProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );
    }

    private function initValidParameterSet(): array
    {
        $testCode = 'test-code';
        $testUid = 'test-uid';
        $testDob = 'test-dob';
        $testActorId = 1;
        $this->testActorUid = '123456789012';

        $mockLpa = [
            'uId' => $testUid,
        ];

        $mockActor = [
            'details' => [
                'dob' => $testDob,
                'id'  => $testActorId,
                'uId' => $this->testActorUid,
            ],
        ];

        $this->codeValidatorProphecy->validateCode($testCode, $testUid, $testDob)
            ->willReturn($this->testActorUid)
            ->shouldBeCalled();

        $this->lpaServiceProphecy->getByUid($testUid)->willReturn(
            new Repository\Response\Lpa($mockLpa, null)
        )->shouldBeCalled();

        $this->resolveActorProphecy
            ->__invoke(Argument::type('array'), Argument::type('string'))
            ->willReturn($mockActor)
            ->shouldBeCalled();

        return [
            $testCode,
            $testUid,
            $testDob,
            $testActorId,
            $this->testActorUid,
            $mockLpa,
            $mockActor,
        ];
    }
}
