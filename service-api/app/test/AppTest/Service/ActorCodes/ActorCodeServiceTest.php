<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

class ActorCodeServiceTest extends TestCase
{
    /**
     * @var Repository\ActorCodesInterface
     */
    private $actorCodesInterfaceProphecy;

    /**
     * @var LpaService
     */
    private $lpaServiceProphecy;

    /**
     * @var Repository\UserLpaActorMapInterface
     */
    private $userLpaActorMapInterfaceProphecy;

    /**
     * @var LoggerInterface
     */
    private $loggerProphecy;

    public function setUp()
    {
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->actorCodesInterfaceProphecy = $this->prophesize(Repository\ActorCodesInterface::class);
        $this->userLpaActorMapInterfaceProphecy = $this->prophesize(Repository\UserLpaActorMapInterface::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }

    /** @test */
    public function confirmation_with_invalid_details()
    {
        $service = $this->getActorCodeService();

        $result = $service->confirmDetails('test-code', 'test-uid', 'test-dob', 'test-user');

        $this->assertNull($result);
    }

    /** @test */
    public function confirmation_with_valid_details()
    {
        $this->initValidParameterSet();

        $this->actorCodesInterfaceProphecy->flagCodeAsUsed('test-code')
            ->shouldBeCalled();

        //---

        $service = $this->getActorCodeService();

        $result = $service->confirmDetails('test-code', 'test-uid', 'test-dob', 'test-user');

        // We expect a uuid4 back.
        $this->assertRegExp('|^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$|', $result);
    }

    /** @test */
    public function confirmation_with_valid_details_fails_flag_as_used()
    {
        $this->initValidParameterSet();

        $this->actorCodesInterfaceProphecy->flagCodeAsUsed('test-code')
            ->willThrow(new \Exception());

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
    public function successful_validation()
    {
        $testCode = 'test-code';
        $testUid = 'test-uid';
        $testDob = 'test-dob';

        $mockLpa = [
            'uId' => $testUid,
        ];

        $mockActor = [
            'details' => ['dob' => $testDob],
        ];

        $this->actorCodesInterfaceProphecy->get($testCode)->willReturn(
            [
                'Active' => true,
                'SiriusUid' => $testUid,
                'ActorLpaId' => 1,
                'ActorCode' => $testCode,
            ]
        )->shouldBeCalled();

        $this->lpaServiceProphecy->getByUid($testUid)->willReturn(
            new Repository\Response\Lpa($mockLpa, null)
        )->shouldBeCalled();

        $this->lpaServiceProphecy->lookupActorInLpa(Argument::type('array'), Argument::type('int'))
            ->willReturn($mockActor)
            ->shouldBeCalled();

        //---

        $service = $this->getActorCodeService();

        $result = $service->validateDetails($testCode, $testUid, $testDob);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('lpa', $result);
        $this->assertArrayHasKey('actor', $result);
        $this->assertEquals($mockLpa, $result['lpa']);
        $this->assertEquals($mockActor, $result['actor']);
    }

    /** @test */
    public function validation_with_invalid_actor()
    {
        $testCode = 'test-code';
        $testUid = 'test-uid';
        $testDob = 'test-dob';

        $this->actorCodesInterfaceProphecy->get($testCode)->willReturn(
            [
                'Active' => true,
                'SiriusUid' => $testUid,
                'ActorLpaId' => 1,
                'ActorCode' => 'different-actor-code',
            ]
        )->shouldBeCalled();

        $this->lpaServiceProphecy->getByUid($testUid)->willReturn(
            new Repository\Response\Lpa([], null)
        )->shouldBeCalled();

        $this->lpaServiceProphecy->lookupActorInLpa(Argument::type('array'), Argument::type('int'))
            ->willReturn(
                [
                    'details' => ['dob' => $testDob],
                ]
            )->shouldBeCalled();

        //---

        $service = $this->getActorCodeService();

        $result = $service->validateDetails($testCode, $testUid, $testDob);

        $this->assertNull($result);
    }

    /** @test */
    public function validation_with_invalid_actor_code()
    {
        $testCode = 'test-code';
        $testUid = 'test-uid';
        $testDob = 'test-dob';

        $this->actorCodesInterfaceProphecy->get($testCode)->willReturn(null)->shouldBeCalled();

        //---

        $service = $this->getActorCodeService();

        $result = $service->validateDetails($testCode, $testUid, $testDob);

        $this->assertNull($result);
    }

    /** @test */
    public function validation_with_invalid_dob()
    {
        $testCode = 'test-code';
        $testUid = 'test-uid';
        $testDob = 'test-dob';

        $this->actorCodesInterfaceProphecy->get($testCode)->willReturn(
            [
                'Active' => true,
                'SiriusUid' => $testUid,
                'ActorLpaId' => 1,
                'ActorCode' => $testCode,
            ]
        )->shouldBeCalled();

        $this->lpaServiceProphecy->getByUid($testUid)->willReturn(
            new Repository\Response\Lpa(
                [
                    'uId' => $testUid,
                ],
                null
            )
        )->shouldBeCalled();

        $this->lpaServiceProphecy->lookupActorInLpa(Argument::type('array'), Argument::type('int'))
            ->willReturn(
                [
                    'details' => ['dob' => 'different-dob'],
                ]
            )->shouldBeCalled();

        //---

        $service = $this->getActorCodeService();

        $result = $service->validateDetails($testCode, $testUid, $testDob);

        $this->assertNull($result);
    }

    /** @test */
    public function validation_with_invalid_uid()
    {
        $testCode = 'test-code';
        $testUid = 'test-uid';
        $testDob = 'test-dob';

        $this->actorCodesInterfaceProphecy->get($testCode)->willReturn(
            [
                'Active' => true,
                'SiriusUid' => $testDob,
                'ActorLpaId' => 1,
                'ActorCode' => $testCode,
            ]
        )->shouldBeCalled();

        $this->lpaServiceProphecy->getByUid($testDob)->willReturn(
            new Repository\Response\Lpa(
                [
                    'uId' => 'different-uid',
                ],
                null
            )
        )->shouldBeCalled();

        $this->lpaServiceProphecy->lookupActorInLpa(Argument::type('array'), Argument::type('int'))->willReturn(
            [
                'details' => ['dob' => $testDob],
            ]
        )->shouldBeCalled();

        //---

        $service = $this->getActorCodeService();

        $result = $service->validateDetails($testCode, $testUid, $testDob);

        $this->assertNull($result);
    }

    /** @test */
    public function validation_with_missing_actor()
    {
        $testCode = 'test-code';
        $testUid = 'test-uid';
        $testDob = 'test-dob';

        $mockSiriusId = 'mock-id';

        $mockLpa = new Repository\Response\Lpa([], null);

        $this->actorCodesInterfaceProphecy->get($testCode)->willReturn(
            [
                'Active' => true,
                'SiriusUid' => $mockSiriusId,
                'ActorLpaId' => 1,
            ]
        )->shouldBeCalled();

        $this->lpaServiceProphecy->getByUid($mockSiriusId)->willReturn(
            $mockLpa
        )->shouldBeCalled();

        $this->lpaServiceProphecy->lookupActorInLpa(Argument::type('array'), Argument::type('int'))
            ->willReturn(null)
            ->shouldBeCalled();

        //---

        $service = $this->getActorCodeService();

        $result = $service->validateDetails($testCode, $testUid, $testDob);

        $this->assertNull($result);
    }

    //-------------------------------------

    /** @test */
    public function validation_with_missing_lpa()
    {
        $testCode = 'test-code';
        $testUid = 'test-uid';
        $testDob = 'test-dob';

        $mockSiriusId = 'mock-id';

        $this->actorCodesInterfaceProphecy->get($testCode)->willReturn(
            [
                'Active' => true,
                'SiriusUid' => $mockSiriusId,
            ]
        )->shouldBeCalled();

        $this->lpaServiceProphecy->getByUid($mockSiriusId)->willReturn(null)->shouldBeCalled();

        //---

        $service = $this->getActorCodeService();

        $result = $service->validateDetails($testCode, $testUid, $testDob);

        $this->assertNull($result);
    }

    /** @test */
    public function validation_with_valid_actor_code_that_is_inactive()
    {
        $testCode = 'test-code';
        $testUid = 'test-uid';
        $testDob = 'test-dob';

        $this->actorCodesInterfaceProphecy->get($testCode)->willReturn(
            [
                'Active' => false,
            ]
        )->shouldBeCalled();

        $service = $this->getActorCodeService();

        $result = $service->validateDetails($testCode, $testUid, $testDob);

        $this->assertNull($result);
    }

    private function getActorCodeService(): ActorCodeService
    {
        return new ActorCodeService(
            $this->actorCodesInterfaceProphecy->reveal(),
            $this->userLpaActorMapInterfaceProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );
    }

    private function initValidParameterSet()
    {
        $testCode = 'test-code';
        $testUid = 'test-uid';
        $testDob = 'test-dob';
        $testActorId = 1;

        $mockLpa = [
            'uId' => $testUid,
        ];

        $mockActor = [
            'details' => [
                'dob' => $testDob,
                'id' => $testActorId,
            ],
        ];

        $this->actorCodesInterfaceProphecy->get($testCode)->willReturn(
            [
                'Active' => true,
                'SiriusUid' => $testUid,
                'ActorLpaId' => $testActorId,
                'ActorCode' => $testCode,
            ]
        )->shouldBeCalled();

        $this->lpaServiceProphecy->getByUid($testUid)->willReturn(
            new Repository\Response\Lpa($mockLpa, null)
        )->shouldBeCalled();

        $this->lpaServiceProphecy->lookupActorInLpa(Argument::type('array'), Argument::type('int'))
            ->willReturn($mockActor)
            ->shouldBeCalled();
    }

}
