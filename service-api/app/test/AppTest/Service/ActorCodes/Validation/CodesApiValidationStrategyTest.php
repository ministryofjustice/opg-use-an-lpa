<?php

declare(strict_types=1);

namespace AppTest\Service\ActorCodes\Validation;

use App\DataAccess\ApiGateway\ActorCodes;
use App\DataAccess\Repository\Response\ActorCode;
use App\DataAccess\Repository\Response\Lpa;
use App\Exception\ActorCodeMarkAsUsedException;
use App\Exception\ActorCodeValidationException;
use App\Service\ActorCodes\Validation\CodesApiValidationStrategy;
use App\Service\Lpa\LpaManagerInterface;
use App\Service\Lpa\ResolveActor;
use App\Service\Lpa\ResolveActor\ActorType;
use App\Service\Lpa\ResolveActor\LpaActor;
use App\Service\Lpa\SiriusLpa;
use App\Service\Lpa\SiriusPerson;
use DateTime;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class CodesApiValidationStrategyTest extends TestCase
{
    use ProphecyTrait;

    private ActorCodes|ObjectProphecy $actorCodeApiProphecy;
    private LoggerInterface|ObjectProphecy $loggerProphecy;
    private LpaManagerInterface|ObjectProphecy $lpaManagerProphecy;
    private ResolveActor|ObjectProphecy $resolveActorProphecy;

    public function initDependencies(): void
    {
        $this->actorCodeApiProphecy = $this->prophesize(ActorCodes::class);
        $this->lpaManagerProphecy   = $this->prophesize(LpaManagerInterface::class);
        $this->loggerProphecy       = $this->prophesize(LoggerInterface::class);
        $this->resolveActorProphecy = $this->prophesize(ResolveActor::class);
    }

    private function getCodesApiValidationStrategy()
    {
        return new CodesApiValidationStrategy(
            $this->actorCodeApiProphecy->reveal(),
            $this->lpaManagerProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->resolveActorProphecy->reveal()
        );
    }

    #[Test]
    public function it_will_validate_a_code(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => '123456789',
            ],
            new DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('actor-code', 'lpa-uid', '1948-11-01')
            ->willReturn($actor);

        $lpa = new Lpa(
            new SiriusLpa(
                [
                    'uId' => 'lpa-uid',
                ],
                $this->loggerProphecy->reveal(),
            ),
            new DateTime('now')
        );

        $this->lpaManagerProphecy
            ->getByUid('lpa-uid')
            ->willReturn($lpa);

        $this->resolveActorProphecy
            ->__invoke($lpa->getData(), '123456789')
            ->willReturn(
                new LpaActor(
                    new SiriusPerson(
                        [
                            'uId'          => 'actor-uid',
                            'dob'          => '1948-11-01',
                        ],
                        $this->loggerProphecy->reveal(),
                    ),
                    ActorType::ATTORNEY,
                ),
            );

        $strategy = $this->getCodesApiValidationStrategy();

        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', '1948-11-01');

        $this->assertEquals('123456789', $actorUId);
    }

    #[Test]
    public function it_wont_validate_a_nonexistant_code(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => null,
            ],
            new DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('bad-actor-code', 'lpa-uid', 'actor-dob')
            ->willReturn($actor);

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('bad-actor-code', 'lpa-uid', 'actor-dob');
    }

    #[Test]
    public function it_wont_validate_an_expired_code(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => null,
            ],
            new DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('expired-actor-code', 'lpa-uid', 'actor-dob')
            ->willReturn($actor);

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('expired-actor-code', 'lpa-uid', 'actor-dob');
    }

    #[Test]
    public function it_wont_validate_a_code_when_lpa_not_found(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => 'actor-uid',
            ],
            new DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('actor-code', 'lpa-uid', 'actor-dob')
            ->willReturn($actor);

        $this->lpaManagerProphecy
            ->getByUid('lpa-uid')
            ->shouldBeCalled()
            ->willReturn(null);

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    #[Test]
    public function it_wont_validate_a_code_with_a_mismatched_actor(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => '123456789',
            ],
            new DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('actor-code', 'lpa-uid', '1948-11-01')
            ->willReturn($actor);

        $lpa = new Lpa(
            new SiriusLpa(
                [
                    'uId' => 'lpa-uid',
                ],
                $this->loggerProphecy->reveal(),
            ),
            new DateTime('now')
        );

        $this->lpaManagerProphecy
            ->getByUid('lpa-uid')
            ->shouldBeCalled()
            ->willReturn($lpa);

        $this->resolveActorProphecy
            ->__invoke($lpa->getData(), '123456789')
            ->willReturn(null);

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', '1948-11-01');
    }

    #[Test]
    public function it_wont_validate_a_code_with_a_bad_dob(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => '123456789',
            ],
            new DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('actor-code', 'lpa-uid', 'actor-dob')
            ->willReturn($actor);

        $lpa = new Lpa(
            new SiriusLpa(
                [
                    'uId' => 'lpa-uid',
                ],
                $this->loggerProphecy->reveal(),
            ),
            new DateTime('now')
        );

        $this->lpaManagerProphecy
            ->getByUid('lpa-uid')
            ->shouldBeCalled()
            ->willReturn($lpa);

        $this->resolveActorProphecy
            ->__invoke($lpa->getData(), '123456789')
            ->shouldBeCalled()
            ->willReturn(
                new LpaActor(
                    new SiriusPerson(
                        [
                            'uId' => 'actor-uid',
                            'dob' => '1975-10-05',
                        ],
                        $this->loggerProphecy->reveal(),
                    ),
                    ActorType::ATTORNEY,
                ),
            );

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    #[Test]
    public function it_wont_validate_a_code_with_a_bad_dob_combined_format(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => '123456789',
            ],
            new DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('actor-code', 'lpa-uid', 'actor-dob')
            ->willReturn($actor);

        $lpa = new Lpa(
            new SiriusLpa(
                [
                    'uId' => 'lpa-uid',
                ],
                $this->loggerProphecy->reveal(),
            ),
            new DateTime('now')
        );

        $this->lpaManagerProphecy
            ->getByUid('lpa-uid')
            ->shouldBeCalled()
            ->willReturn($lpa);

        $this->resolveActorProphecy
            ->__invoke($lpa->getData(), '123456789')
            ->shouldBeCalled()
            ->willReturn(
                new LpaActor(
                    new SiriusPerson(
                        [
                            'uId' => 'actor-uid',
                            'dob' => '1975-10-05',
                        ],
                        $this->loggerProphecy->reveal(),
                    ),
                    ActorType::ATTORNEY,
                ),
            );

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    #[Test]
    public function it_will_handle_an_exception_when_validating_a_code(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => 'actor-uid',
            ],
            new DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('actor-code', 'lpa-uid', 'actor-dob')
            ->willThrow(new Exception('A serious error has occured'));

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(Exception::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', 'actor-dob');
    }

    #[Test]
    public function it_will_flag_a_code_as_used(): void
    {
        $this->initDependencies();

        $this->actorCodeApiProphecy
            ->flagCodeAsUsed('actor-code')
            ->shouldBeCalled();

        $strategy = $this->getCodesApiValidationStrategy();

        $strategy->flagCodeAsUsed('actor-code');
    }

    #[Test]
    public function it_will_handle_an_exception_when_flagging_a_code_as_used(): void
    {
        $this->initDependencies();

        $this->actorCodeApiProphecy
            ->flagCodeAsUsed('actor-code')
            ->willThrow(new Exception());

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeMarkAsUsedException::class);
        $strategy->flagCodeAsUsed('actor-code');
    }

    #[Test]
    public function it_wont_validate_a_code_with_a_bad_dob_for_trust_corporation(): void
    {
        $this->initDependencies();

        $actor = new ActorCode(
            [
                'actor' => '123456789',
            ],
            new DateTime('now')
        );

        $this->actorCodeApiProphecy
            ->validateCode('actor-code', 'lpa-uid', '1948-11-01')
            ->willReturn($actor);

        $lpa = new Lpa(
            new SiriusLpa(
                [
                    'donor' => [
                        'id'          => 1,
                        'uId'         => 'donor-uid',
                        'dob'         => '1975-10-05',
                        'salutation'  => 'Mr',
                        'firstname'   => 'Test',
                        'middlenames' => '',
                        'surname'     => 'User',
                        'addresses'   => [
                            0 => [],
                        ],
                    ],
                    'uId'   => 'lpa-uid',
                ],
                $this->loggerProphecy->reveal(),
            ),
            new DateTime('now')
        );

        $this->lpaManagerProphecy
            ->getByUid('lpa-uid')
            ->shouldBeCalled()
            ->willReturn($lpa);

        $this->resolveActorProphecy
            ->__invoke($lpa->getData(), '123456789')
            ->shouldBeCalled()
            ->willReturn(
                new LpaActor(
                    [
                        'id'           => 9,
                        'uId'          => 123456789,
                        'firstname'    => 'trust',
                        'surname'      => 'corporation',
                        'companyName'  => 'trust corporation ltd',
                        'systemStatus' => true,
                    ],
                    ActorType::TRUST_CORPORATION,
                ),
            );

        $strategy = $this->getCodesApiValidationStrategy();

        $this->expectException(ActorCodeValidationException::class);
        $actorUId = $strategy->validateCode('actor-code', 'lpa-uid', '1948-11-01');
    }
}
