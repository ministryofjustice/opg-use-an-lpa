<?php

declare(strict_types=1);

namespace AppTest\Service\ActorCodes\Validation;

use App\DataAccess\Repository\ActorCodesInterface;
use App\DataAccess\Repository\Response\Lpa;
use App\Exception\ActorCodeMarkAsUsedException;
use App\Exception\ActorCodeValidationException;
use App\Service\ActorCodes\Validation\DynamoCodeValidationStrategy;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\ResolveActor;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class DynamoCodeValidationStrategyTest extends TestCase
{
    /** @var ObjectProphecy|ActorCodesInterface */
    private ObjectProphecy $actorCodeRepositoryProphecy;

    /** @var ObjectProphecy|LoggerInterface */
    private ObjectProphecy $loggerProphecy;

    /** @var ObjectProphecy|LpaService */
    private ObjectProphecy $lpaServiceProphecy;

    /** @var ObjectProphecy|ResolveActor */
    private ObjectProphecy $resolveActorProphecy;

    public function initDependencies(): void
    {
        $this->actorCodeRepositoryProphecy = $this->prophesize(ActorCodesInterface::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->resolveActorProphecy = $this->prophesize(ResolveActor::class);
    }

    /** @test */
    public function it_will_validate_a_code(): void
    {
        $this->initDependencies();

        $this->actorCodeRepositoryProphecy
            ->get('actor-code')
            ->willReturn(
                [
                    'Active'     => true,
                    'SiriusUid'  => 'lpa-uid',
                    'ActorLpaId' => 'actor-lpa-id'
                ]
            );

        $lpa = new Lpa(
            [
                'uId' => 'lpa-uid'
            ],
            new \DateTime('now')
        );

        $this->lpaServiceProphecy
            ->getByUid('lpa-uid')
            ->willReturn($lpa);

        $this->resolveActorProphecy->__invoke($lpa->getData(), 'actor-lpa-id')
            ->willReturn(
                [
                    'details' => [
                        'uId' => 'actor-uid',
                        'dob' => 'actor-dob'
                    ]
                ]
            );

        $strategy = new DynamoCodeValidationStrategy(
            $this->actorCodeRepositoryProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );

        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');

        $this->assertEquals('actor-uid', $actorUId);
    }

    /** @test */
    public function it_wont_validate_a_nonexistant_code(): void
    {
        $this->initDependencies();

        $strategy = new DynamoCodeValidationStrategy(
            $this->actorCodeRepositoryProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    /** @test */
    public function it_wont_validate_an_expired_code(): void
    {
        $this->initDependencies();

        $this->actorCodeRepositoryProphecy
            ->get('actor-code')
            ->willReturn(
                [
                    'Active' => false
                ]
            );

        $strategy = new DynamoCodeValidationStrategy(
            $this->actorCodeRepositoryProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    /** @test */
    public function it_wont_validate_a_mismatched_lpa_uid(): void
    {
        $this->initDependencies();

        $this->actorCodeRepositoryProphecy
            ->get('actor-code')
            ->willReturn(
                [
                    'Active'     => true,
                    'SiriusUid'  => 'different-lpa-uid',
                    'ActorLpaId' => 'actor-lpa-id'
                ]
            );

        $strategy = new DynamoCodeValidationStrategy(
            $this->actorCodeRepositoryProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    /** @test */
    public function it_wont_validate_a_code_when_lpa_not_found(): void
    {
        $this->initDependencies();

        $this->actorCodeRepositoryProphecy
            ->get('actor-code')
            ->willReturn(
                [
                    'Active'     => true,
                    'SiriusUid'  => 'lpa-uid',
                    'ActorLpaId' => 'actor-lpa-id'
                ]
            );

        $this->lpaServiceProphecy
            ->getByUid('lpa-uid')
            ->willReturn(null);

        $strategy = new DynamoCodeValidationStrategy(
            $this->actorCodeRepositoryProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    /** @test */
    public function it_wont_validate_a_code_with_a_mismatched_actor(): void
    {
        $this->initDependencies();

        $this->actorCodeRepositoryProphecy
            ->get('actor-code')
            ->willReturn(
                [
                    'Active'     => true,
                    'SiriusUid'  => 'lpa-uid',
                    'ActorLpaId' => 'actor-lpa-id'
                ]
            );

        $lpa = new Lpa(
            [
                'uId' => 'lpa-uid'
            ],
            new \DateTime('now')
        );

        $this->lpaServiceProphecy
            ->getByUid('lpa-uid')
            ->willReturn($lpa);

        $this->resolveActorProphecy->__invoke($lpa->getData(), 'actor-lpa-id')
            ->willReturn(null);

        $strategy = new DynamoCodeValidationStrategy(
            $this->actorCodeRepositoryProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    /** @test */
    public function it_wont_validate_a_code_with_a_bad_dob(): void
    {
        $this->initDependencies();

        $this->actorCodeRepositoryProphecy
            ->get('actor-code')
            ->willReturn(
                [
                    'Active'     => true,
                    'SiriusUid'  => 'lpa-uid',
                    'ActorLpaId' => 'actor-lpa-id'
                ]
            );

        $lpa = new Lpa(
            [
                'uId' => 'lpa-uid'
            ],
            new \DateTime('now')
        );

        $this->lpaServiceProphecy
            ->getByUid('lpa-uid')
            ->willReturn($lpa);

        $this->resolveActorProphecy->__invoke($lpa->getData(), 'actor-lpa-id')
            ->willReturn(
                [
                    'details' => [
                        'uId' => 'actor-uid',
                        'dob' => 'different-dob'
                    ]
                ]
            );

        $strategy = new DynamoCodeValidationStrategy(
            $this->actorCodeRepositoryProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    /** @test */
    public function it_will_flag_a_code_as_used(): void
    {
        $this->initDependencies();

        $this->actorCodeRepositoryProphecy
            ->flagCodeAsUsed('actor-code')
            ->shouldBeCalled();

        $strategy = new DynamoCodeValidationStrategy(
            $this->actorCodeRepositoryProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );

        $strategy->flagCodeAsUsed('actor-code');
    }

    /** @test */
    public function it_will_handle_an_exception_when_flagging_a_code_as_used(): void
    {
        $this->initDependencies();

        $this->actorCodeRepositoryProphecy
            ->flagCodeAsUsed('actor-code')
            ->willThrow(new \Exception());

        $strategy = new DynamoCodeValidationStrategy(
            $this->actorCodeRepositoryProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );

        $this->expectException(ActorCodeMarkAsUsedException::class);
        $strategy->flagCodeAsUsed('actor-code');
    }
}
