<?php

declare(strict_types=1);

namespace AppTest\Service\ActorCodes\Validation;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\Repository\Response\ActorCode;
use App\DataAccess\Repository\Response\Lpa;
use App\Exception\ActorCodeMarkAsUsedException;
use App\Exception\ActorCodeValidationException;
use App\Service\ActorCodes\Validation\CodesApiValidationStrategy;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\ResolveActor;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class CodesApiValidationStrategyTest extends TestCase
{
    /** @var ObjectProphecy|ActorCodes */
    private ObjectProphecy $actorCodeApiProphecy;

    /** @var ObjectProphecy|LoggerInterface */
    private ObjectProphecy $loggerProphecy;

    /** @var ObjectProphecy|LpaService */
    private ObjectProphecy $lpaServiceProphecy;

    /** @var ObjectProphecy|ResolveActor */
    private ObjectProphecy $resolveActorProphecy;

    public function initDependencies(): void
    {
        $this->actorCodeApiProphecy = $this->prophesize(ActorCodes::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->resolveActorProphecy = $this->prophesize(ResolveActor::class);
    }

    private function getCodesApiValidationStrategy()
    {
        return new CodesApiValidationStrategy(
            $this->actorCodeApiProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );
    }

    /** @test */
    public function it_will_validate_a_code(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => 'actor-uid',
            ],
            new \DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('actor-code', 'lpa-uid', 'actor-dob')
            ->willReturn($actor);

        $lpa = new Lpa(
            [
                'uId' => 'lpa-uid'
            ],
            new \DateTime('now')
        );

        $this->lpaServiceProphecy
            ->getByUid('lpa-uid')
            ->willReturn($lpa);

        $this->resolveActorProphecy
            ->__invoke($lpa->getData(), 'actor-uid')
            ->willReturn(
                [
                    'details' => [
                        'uId' => 'actor-uid',
                        'dob' => 'actor-dob'
                    ]
                ]
            );

        $strategy = $this->getCodesApiValidationStrategy();

        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');

        $this->assertEquals('actor-uid', $actorUId);
    }

    /** @test */
    public function it_wont_validate_a_nonexistant_code(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => null,
            ],
            new \DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('bad-actor-code', 'lpa-uid', 'actor-dob')
            ->willReturn($actor);

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('bad-actor-code', 'lpa-uid', 'actor-dob');
    }

    /** @test */
    public function it_wont_validate_an_expired_code(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => null,
            ],
            new \DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('expired-actor-code', 'lpa-uid', 'actor-dob')
            ->willReturn($actor);

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('expired-actor-code', 'lpa-uid', 'actor-dob');
    }

    /** @test */
    public function it_wont_validate_a_code_when_lpa_not_found(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => 'actor-uid',
            ],
            new \DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('actor-code', 'lpa-uid', 'actor-dob')
            ->willReturn($actor);

        $this->lpaServiceProphecy
            ->getByUid('lpa-uid')
            ->shouldBeCalled()
            ->willReturn(null);

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    /** @test */
    public function it_wont_validate_a_code_with_a_mismatched_actor(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => 'actor-uid',
            ],
            new \DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('actor-code', 'lpa-uid', 'actor-dob')
            ->willReturn($actor);

        $lpa = new Lpa(
            [
                'uId' => 'lpa-uid'
            ],
            new \DateTime('now')
        );

        $this->lpaServiceProphecy
            ->getByUid('lpa-uid')
            ->shouldBeCalled()
            ->willReturn($lpa);

        $this->resolveActorProphecy
            ->__invoke($lpa->getData(), 'actor-uid')
            ->willReturn(null);

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    /** @test */
    public function it_wont_validate_a_code_with_a_bad_dob(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => 'actor-uid',
            ],
            new \DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('actor-code', 'lpa-uid', 'actor-dob')
            ->willReturn($actor);

        $lpa = new Lpa(
            [
                'uId' => 'lpa-uid'
            ],
            new \DateTime('now')
        );

        $this->lpaServiceProphecy
            ->getByUid('lpa-uid')
            ->shouldBeCalled()
            ->willReturn($lpa);

        $this->resolveActorProphecy
            ->__invoke($lpa->getData(), 'actor-uid')
            ->shouldBeCalled()
            ->willReturn(
                [
                    'details' => [
                        'uId' => 'actor-uid',
                        'dob' => 'different-dob'
                    ]
                ]
            );

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    /** @test */
    public function it_will_handle_an_exception_when_validating_a_code(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => 'actor-uid',
            ],
            new \DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('actor-code', 'lpa-uid', 'actor-dob')
            ->willThrow(new Exception('A serious error has occured'));

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(Exception::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    /** @test */
    public function it_will_flag_a_code_as_used(): void
    {
        $this->initDependencies();

        $this->actorCodeApiProphecy
            ->flagCodeAsUsed('actor-code')
            ->shouldBeCalled();

        $strategy = $this->getCodesApiValidationStrategy();

        $strategy->flagCodeAsUsed('actor-code');
    }

    /** @test */
    public function it_will_handle_an_exception_when_flagging_a_code_as_used(): void
    {
        $this->initDependencies();

        $this->actorCodeApiProphecy
            ->flagCodeAsUsed('actor-code')
            ->willThrow(new \Exception());

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeMarkAsUsedException::class);
        $strategy->flagCodeAsUsed('actor-code');
    }
}
