<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\Repository;
use App\DataAccess\Repository\ActorCodesInterface;
use App\DataAccess\Repository\KeyCollisionException;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ActorCodeMarkAsUsedException;
use App\Exception\ActorCodeValidationException;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\ActorCodes\CodeValidationStrategyInterface;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\ResolveActor;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class ActorCodeServiceTest extends TestCase
{
    /**
     * @var CodeValidationStrategyInterface|ObjectProphecy
     */
    private $codeValidatorProphecy;

    /**
     * @var LpaService|ObjectProphecy
     */
    private $lpaServiceProphecy;

    /**
     * @var UserLpaActorMapInterface|ObjectProphecy
     */
    private $userLpaActorMapInterfaceProphecy;

    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $loggerProphecy;

    /**
     * @var ResolveActor|ObjectProphecy
     */
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
            Argument::that(
                function (string $id) {
                    $this->assertRegExp('|^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$|', $id);
                    return true;
                }
            ),
            Argument::exact('test-user'),
            Argument::exact('test-uid'),
            Argument::exact(1)
        )->shouldBeCalled();

        $service = $this->getActorCodeService();

        $result = $service->confirmDetails('test-code', 'test-uid', 'test-dob', 'test-user');

        // We expect a uuid4 back.
        $this->assertRegExp('|^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$|', $result);
    }

    /** @test */
    public function confirmation_with_valid_details_fails_flag_as_used(): void
    {
        $this->initValidParameterSet();

        $this->codeValidatorProphecy->flagCodeAsUsed('test-code')
            ->willThrow(new ActorCodeMarkAsUsedException());

        $this->userLpaActorMapInterfaceProphecy->create(
            Argument::that(
                function (string $id) {
                    $this->assertRegExp('|^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$|', $id);
                    return true;
                }
            ),
            Argument::exact('test-user'),
            Argument::exact('test-uid'),
            Argument::exact(1)
        )->shouldBeCalled();

        $this->userLpaActorMapInterfaceProphecy->delete(
            Argument::that(
                function (string $id) {
                    $this->assertRegExp('|^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$|', $id);
                    return true;
                }
            )
        )->shouldBeCalled();

        //---

        $service = $this->getActorCodeService();

        $result = $service->confirmDetails('test-code', 'test-uid', 'test-dob', 'test-user');
    }

    /** @test */
    public function confirmation_with_vaild_details_has_key_collision(): void
    {
        $this->initValidParameterSet();

        $this->codeValidatorProphecy->flagCodeAsUsed('test-code')
            ->willReturn('id-of-db-row')
            ->shouldBeCalled();

        // We call the create function multiple times till it works.
        $createCalls = 0;
        $this->userLpaActorMapInterfaceProphecy->create(
            Argument::that(
                function (string $id) {
                    $this->assertRegExp('|^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$|', $id);
                    return true;
                }
            ),
            Argument::exact('test-user'),
            Argument::exact('test-uid'),
            Argument::exact(1)
        )->will(function () use (&$createCalls) {
            if ($createCalls > 0) {
                return;
            }

            $createCalls++;
            throw new KeyCollisionException();
        });

        $service = $this->getActorCodeService();

        $result = $service->confirmDetails('test-code', 'test-uid', 'test-dob', 'test-user');

        // We expect a uuid4 back.
        $this->assertRegExp('|^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$|', $result);
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
            $this->loggerProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );
    }

    private function initValidParameterSet(): array
    {
        $testCode = 'test-code';
        $testUid = 'test-uid';
        $testDob = 'test-dob';
        $testActorId = 1;
        $testActorUid = '123456789012';

        $mockLpa = [
            'uId' => $testUid,
        ];

        $mockActor = [
            'details' => [
                'dob' => $testDob,
                'id'  => $testActorId,
                'uId' => $testActorUid,
            ],
        ];

        $this->codeValidatorProphecy->validateCode($testCode, $testUid, $testDob)
            ->willReturn($testActorUid)
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
            $testActorUid,
            $mockLpa,
            $mockActor,
        ];
    }
}
