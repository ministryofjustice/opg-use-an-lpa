<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\ApiGateway\DataStoreLpas;
use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\Repository\Response\Lpa;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Entity\LpaStore\LpaStore;
use App\Entity\Sirius\SiriusLpa;
use App\Service\Lpa\Combined\FilterActiveActors;
use App\Service\Lpa\Combined\ResolveLpaTypes;
use App\Service\Lpa\CombinedLpaManager;
use App\Service\Lpa\IsValidLpa;
use App\Service\Lpa\LpaDataFormatter;
use App\Service\Lpa\ResolveActor;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class CombinedLpaManagerTest extends TestCase
{
    use ProphecyTrait;

    private CombinedLpaManager $combinedLpaManager;
    private DataStoreLpas|ObjectProphecy $dataStoreLpasProphecy;
    private FilterActiveActors|ObjectProphecy $filterActiveActorsProphecy;
    private IsValidLpa|ObjectProphecy $isValidLpaProphecy;
    private ResolveActor|ObjectProphecy $resolveActorProphecy;
    private ResolveLpaTypes|ObjectProphecy $resolveLpaTypesProphecy;
    private SiriusLpas|ObjectProphecy $siriusLpasProphecy;
    private UserLpaActorMapInterface|ObjectProphecy $userLpaActorMapInterfaceProphecy;

    public function setUp(): void
    {
        $this->userLpaActorMapInterfaceProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $this->resolveLpaTypesProphecy          = $this->prophesize(ResolveLpaTypes::class);
        $this->siriusLpasProphecy               = $this->prophesize(SiriusLpas::class);
        $this->dataStoreLpasProphecy            = $this->prophesize(DataStoreLpas::class);
        $this->resolveActorProphecy             = $this->prophesize(ResolveActor::class);
        $this->isValidLpaProphecy               = $this->prophesize(IsValidLpa::class);
        $this->filterActiveActorsProphecy       = $this->prophesize(FilterActiveActors::class);
    }

    #[Test]
    public function can_get_all_active_for_user()
    {
        $testUserId = 'test-user-id';

        /** @var Lpa<SiriusLpa> $siriusLpaResponse */
        $siriusLpaResponse = new Lpa(
            $this->loadTestSiriusLpaFixture(),
            new DateTimeImmutable('now'),
        );

        /** @var Lpa<LpaStore> $siriusLpaResponse */
        $dataStoreLpaResponse = new Lpa(
            $this->loadTestLpaStoreLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $userLpaActorMapResponse = [
            [
                'Id'      => 'token-2',
                'LpaUid'  => $dataStoreLpaResponse->getData()->uId,
                'ActorId' => $dataStoreLpaResponse->getData()->attorneys[0]->uId,
                'Added'   => new DateTimeImmutable('now'),
            ],
            [
                'Id'         => 'token-3',
                'SiriusUid'  => $siriusLpaResponse->getData()->uId,
                'ActorId'    => $siriusLpaResponse->getData()->attorneys[0]->uId,
                'ActivateBy' => (new DateTimeImmutable('now'))->add(new DateInterval('P1Y'))->getTimeStamp(),
                'Added'      => new DateTimeImmutable('now'),
            ],
        ];

        $this->userLpaActorMapInterfaceProphecy->getByUserId($testUserId)->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesProphecy
            ->__invoke([$userLpaActorMapResponse[0]])
            ->willReturn(
                [
                    [],
                    [$dataStoreLpaResponse->getData()->uId],
                ]
            );
        $this->dataStoreLpasProphecy
            ->lookup([$dataStoreLpaResponse->getData()->uId ?? ''])
            ->willReturn([$dataStoreLpaResponse]);
        $this->resolveActorProphecy
            ->__invoke(
                $dataStoreLpaResponse->getData(),
                $userLpaActorMapResponse[0]['ActorId'],
            )->willReturn(
                new ResolveActor\LpaActor(
                    $dataStoreLpaResponse->getData()->attorneys[0],
                    ResolveActor\ActorType::ATTORNEY
                )
            );
        $this->isValidLpaProphecy->__invoke($dataStoreLpaResponse->getData())->willReturn(true);

        $service = $this->getLpaService();
        $result  = $service->getAllActiveForUser($testUserId);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('token-2', $result);
        $this->assertArrayNotHasKey('token-3', $result);
        $this->assertSame($result['token-2']['user-lpa-actor-token'], 'token-2');
        $this->assertEquals($dataStoreLpaResponse->getData(), $result['token-2']['lpa']);
    }

    #[Test]
    public function can_get_all_for_user()
    {
        $testUserId = 'test-user-id';

        /** @var Lpa<SiriusLpa> $siriusLpaResponse */
        $siriusLpaResponse = new Lpa(
            $this->loadTestSiriusLpaFixture(),
            new DateTimeImmutable('now'),
        );

        /** @var Lpa<LpaStore> $siriusLpaResponse */
        $dataStoreLpaResponse = new Lpa(
            $this->loadTestLpaStoreLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $userLpaActorMapResponse = [
            [
                'Id'        => 'token-1',
                'SiriusUid' => $siriusLpaResponse->getData()->uId,
                'ActorId'   => $siriusLpaResponse->getData()->attorneys[0]->uId,
                'Added'     => new DateTimeImmutable('now'),
            ],
            [
                'Id'      => 'token-2',
                'LpaUid'  => $dataStoreLpaResponse->getData()->uId,
                'ActorId' => $dataStoreLpaResponse->getData()->attorneys[0]->uId,
                'Added'   => new DateTimeImmutable('now'),
            ],
        ];

        $this->userLpaActorMapInterfaceProphecy->getByUserId($testUserId)->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesProphecy
            ->__invoke($userLpaActorMapResponse)
            ->willReturn(
                [
                    [$siriusLpaResponse->getData()->uId],
                    [$dataStoreLpaResponse->getData()->uId],
                ]
            );
        $this->siriusLpasProphecy
            ->lookup([$siriusLpaResponse->getData()->uId])
            ->willReturn(
                [
                    $siriusLpaResponse->getData()->uId ?? '' => $siriusLpaResponse,
                ],
            );
        $this->dataStoreLpasProphecy
            ->lookup([$dataStoreLpaResponse->getData()->uId ?? ''])
            ->willReturn([$dataStoreLpaResponse]);
        $this->resolveActorProphecy
            ->__invoke(
                $siriusLpaResponse->getData(),
                $userLpaActorMapResponse[0]['ActorId'],
            )->willReturn(
                new ResolveActor\LpaActor(
                    $siriusLpaResponse->getData()->attorneys[0],
                    ResolveActor\ActorType::ATTORNEY
                )
            );
        $this->resolveActorProphecy
            ->__invoke(
                $dataStoreLpaResponse->getData(),
                $userLpaActorMapResponse[1]['ActorId'],
            )->willReturn(
                new ResolveActor\LpaActor(
                    $dataStoreLpaResponse->getData()->attorneys[0],
                    ResolveActor\ActorType::ATTORNEY
                )
            );
        $this->isValidLpaProphecy->__invoke($siriusLpaResponse->getData())->willReturn(true);
        $this->isValidLpaProphecy->__invoke($dataStoreLpaResponse->getData())->willReturn(true);

        $service = $this->getLpaService();
        $result  = $service->getAllForUser($testUserId);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('token-1', $result);
        $this->assertArrayHasKey('token-2', $result);
        $this->assertSame($result['token-1']['user-lpa-actor-token'], 'token-1');
        $this->assertSame($result['token-2']['user-lpa-actor-token'], 'token-2');
        $this->assertEquals($siriusLpaResponse->getData(), $result['token-1']['lpa']);
        $this->assertEquals($dataStoreLpaResponse->getData(), $result['token-2']['lpa']);
    }

    #[Test]
    public function returns_missing_if_lpa_not_fetched()
    {
        $testUserId = 'test-user-id';

        $userLpaActorMapResponse = [
            [
                'Id'      => 'token-1',
                'LpaUid'  => '700000000047',
                'ActorId' => '700000000518',
                'Added'   => new DateTimeImmutable('now'),
            ],
        ];

        $this->userLpaActorMapInterfaceProphecy->getByUserId($testUserId)->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesProphecy
            ->__invoke([$userLpaActorMapResponse[0]])
            ->willReturn(
                [
                    ['700000000047'],
                    [],
                ]
            );
        $this->siriusLpasProphecy
            ->lookup(['700000000047'])
            ->willReturn([]);

        $service = $this->getLpaService();
        $result  = $service->getAllActiveForUser($testUserId);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('token-1', $result);
        $this->assertSame($result['token-1']['user-lpa-actor-token'], 'token-1');
        $this->assertSame($result['token-1']['error'], 'NO_LPA_FOUND');
    }

    #[Test]
    public function can_get_by_sirius_uid()
    {
        $testUid = '700000000047';

        $lpaResponse = new Lpa(
            $this->loadTestSiriusLpaFixture(
                overwrite: [
                    'attorneys'         => [
                        ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                        ['id' => 2, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false], // not active
                        ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                        ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
                        ['id' => 5, 'systemStatus' => true], // ghost
                    ],
                    'trustCorporations' => [
                        [
                            'id'           => 6,
                            'companyName'  => 'XYZ Ltd',
                            'systemStatus' => true,
                        ],
                    ],
                ],
            ),
            new DateTimeImmutable('now'),
        );

        $filteredLpa = $this->loadTestSiriusLpaFixture(
            overwrite: [
                'attorneys'         => [
                    ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                    ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                    ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
                ],
                'trustCorporations' => [
                    [
                        'id'           => 6,
                        'companyName'  => 'XYZ Ltd',
                        'systemStatus' => true,
                    ],
                ],
            ],
        );

        $this->siriusLpasProphecy->get($testUid)->willReturn($lpaResponse);
        $this->dataStoreLpasProphecy->get(Argument::any())->shouldNotBeCalled();
        $this->filterActiveActorsProphecy->__invoke($lpaResponse->getData())->willReturn($filteredLpa);

        $service = $this->getLpaService();
        $result  = $service->getByUid($testUid);

        $this->assertEquals($filteredLpa, $result->getData());
    }

    #[Test]
    public function can_get_by_lpastore_uid()
    {
        $testUid = 'M-789Q-P4DF-4UX3';

        $lpaResponse = new Lpa(
            $this->loadTestLpaStoreLpaFixture(
                overwrite: [
                    'attorneys' => [
                        [
                            'uid'        => '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d',
                            'firstNames' => 'Herman',
                            'lastName'   => 'Seakrest',
                            'status'     => 'active',
                        ],
                        [   // replacement
                            'uid'        => '6bbb8221-eded-4835-a1ba-dacdf5ac139c',
                            'firstNames' => 'Test',
                            'lastName'   => 'Testerson',
                            'status'     => 'replacement',
                        ],
                    ],
                ],
            ),
            new DateTimeImmutable('now'),
        );

        // stripping the replacement should match the default then we need to swap out the attorneys
        // as there will *not* be a replacement in there. This is done because there is no way to update
        // the replacement attorneys in the object manually.
        $filteredLpa = $this->loadTestLpaStoreLpaFixture();
        $filteredLpa = $lpaResponse->getData()->withAttorneys($filteredLpa->attorneys);

        $this->dataStoreLpasProphecy->get($testUid)->willReturn($lpaResponse);
        $this->siriusLpasProphecy->get(Argument::any())->shouldNotBeCalled();
        $this->filterActiveActorsProphecy->__invoke($lpaResponse->getData())->willReturn($filteredLpa);

        $service = $this->getLpaService();
        $result  = $service->getByUid($testUid);

        $this->assertEquals($filteredLpa, $result->getData());
    }

    #[Test]
    public function can_get_by_user_lpa_actor_token_sirius()
    {
        $testLpaToken = 'token-1';
        $testUserId   = 'userId-1';

        /** @var Lpa<SiriusLpa> $siriusLpaResponse */
        $siriusLpaResponse = new Lpa(
            $this->loadTestSiriusLpaFixture(),
            new DateTimeImmutable('now'),
        );

        $userLpaActorMapResponse = [
            'Id'         => $testLpaToken,
            'UserId'     => $testUserId,
            'SiriusUid'  => $siriusLpaResponse->getData()->uId,
            'ActorId'    => $siriusLpaResponse->getData()->attorneys[0]->uId,
            'ActivateBy' => (new DateTimeImmutable('now'))->add(new DateInterval('P1Y'))->getTimeStamp(),
            'Added'      => new DateTimeImmutable('now'),
        ];

        $this->userLpaActorMapInterfaceProphecy->get($testLpaToken)->willReturn($userLpaActorMapResponse);
        $this->resolveLpaTypesProphecy
            ->__invoke([$userLpaActorMapResponse])
            ->willReturn(
                [
                    [$siriusLpaResponse->getData()->uId],
                    [],
                ]
            );
        $this->siriusLpasProphecy
            ->get($siriusLpaResponse->getData()->uId ?? '')
            ->willReturn($siriusLpaResponse);
        $this->filterActiveActorsProphecy
            ->__invoke($siriusLpaResponse->getData())
            ->willReturn($siriusLpaResponse->getData());
        $this->resolveActorProphecy
            ->__invoke(
                $siriusLpaResponse->getData(),
                $userLpaActorMapResponse['ActorId'],
            )->willReturn(
                new ResolveActor\LpaActor(
                    $siriusLpaResponse->getData()->attorneys[0],
                    ResolveActor\ActorType::ATTORNEY
                )
            );
        $this->isValidLpaProphecy->__invoke($siriusLpaResponse->getData())->willReturn(true);

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($testLpaToken, $testUserId);

        $this->assertEquals($siriusLpaResponse->getData(), $result['lpa']);
        $this->assertEquals($siriusLpaResponse->getLookupTime()->format(\DateTimeInterface::ATOM), $result['date']);
    }

    #[Test]
    public function can_get_by_user_lpa_actor_token_lpastore()
    {

    }

    #[Test]
    public function can_get_by_viewer_code()
    {
        $this->markTestSkipped();
    }

    private function getLpaService(): CombinedLpaManager
    {
        return new CombinedLpaManager(
            $this->userLpaActorMapInterfaceProphecy->reveal(),
            $this->resolveLpaTypesProphecy->reveal(),
            $this->siriusLpasProphecy->reveal(),
            $this->dataStoreLpasProphecy->reveal(),
            $this->resolveActorProphecy->reveal(),
            $this->isValidLpaProphecy->reveal(),
            $this->filterActiveActorsProphecy->reveal(),
        );
    }

    private function loadTestSiriusLpaFixture(array $overwrite = []): SiriusLpa
    {
        $file    = file_get_contents(__DIR__ . '/../../../fixtures/test_lpa.json');
        $lpaData = json_decode($file, true);
        $lpaData = array_merge($lpaData, $overwrite);

        return (new LpaDataFormatter())->hydrateObject($lpaData);
    }

    private function loadTestLpaStoreLpaFixture(array $overwrite = []): LpaStore
    {
        $lpaData = json_decode(file_get_contents(__DIR__ . '/../../../fixtures/4UX3.json'), true);
        $lpaData = array_merge($lpaData, $overwrite);

        return (new LpaDataFormatter())->hydrateObject($lpaData);
    }
}
