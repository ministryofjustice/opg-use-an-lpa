<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\{Repository\InstructionsAndPreferencesImagesInterface,
    Repository\LpasInterface,
    Repository\UserLpaActorMapInterface,
    Repository\ViewerCodeActivityInterface,
    Repository\ViewerCodesInterface};
use App\DataAccess\Repository\Response\{InstructionsAndPreferencesImages, Lpa};
use App\Enum\InstructionsAndPreferencesImagesResult;
use App\Exception\ApiException;
use App\Exception\NotFoundException;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\{GetAttorneyStatus,
    GetAttorneyStatus\AttorneyStatus,
    GetTrustCorporationStatus,
    GetTrustCorporationStatus\TrustCorporationStatus,
    IsValidLpa,
    ResolveActor,
    ResolveActor\ActorType,
    ResolveActor\LpaActor,
    SiriusLpa,
    SiriusLpaManager,
    SiriusPerson};
use DateInterval;
use DateTime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;

class SiriusLpaManagerTest extends TestCase
{
    use ProphecyTrait;

    private UserLpaActorMapInterface|ObjectProphecy $userLpaActorMapInterfaceProphecy;
    private LpasInterface|ObjectProphecy $lpasInterfaceProphecy;
    private ViewerCodesInterface|ObjectProphecy $viewerCodesInterfaceProphecy;
    private InstructionsAndPreferencesImagesInterface|ObjectProphecy $iapRepositoryProphecy;
    private ViewerCodeActivityInterface|ObjectProphecy $viewerCodeActivityInterfaceProphecy;
    private ResolveActor|ObjectProphecy $resolveActorProphecy;
    private GetAttorneyStatus|ObjectProphecy $getAttorneyStatusProphecy;
    private IsValidLpa|ObjectProphecy $isValidLpaProphecy;
    private GetTrustCorporationStatus|ObjectProphecy $getTrustCorporationStatusProphecy;
    private FeatureEnabled|ObjectProphecy $featureEnabledProphecy;
    private LoggerInterface|ObjectProphecy $loggerProphecy;

    public function setUp(): void
    {
        $this->userLpaActorMapInterfaceProphecy    = $this->prophesize(UserLpaActorMapInterface::class);
        $this->lpasInterfaceProphecy               = $this->prophesize(LpasInterface::class);
        $this->viewerCodesInterfaceProphecy        = $this->prophesize(ViewerCodesInterface::class);
        $this->viewerCodeActivityInterfaceProphecy = $this->prophesize(ViewerCodeActivityInterface::class);
        $this->iapRepositoryProphecy               =
            $this->prophesize(InstructionsAndPreferencesImagesInterface::class);
        $this->resolveActorProphecy                = $this->prophesize(ResolveActor::class);
        $this->getAttorneyStatusProphecy           = $this->prophesize(GetAttorneyStatus::class);
        $this->isValidLpaProphecy                  = $this->prophesize(IsValidLpa::class);
        $this->getTrustCorporationStatusProphecy   = $this->prophesize(GetTrustCorporationStatus::class);
        $this->featureEnabledProphecy              = $this->prophesize(FeatureEnabled::class);
        $this->loggerProphecy                      = $this->prophesize(LoggerInterface::class);
    }

    private function getLpaService(): SiriusLpaManager
    {
        return new SiriusLpaManager(
            $this->userLpaActorMapInterfaceProphecy->reveal(),
            $this->lpasInterfaceProphecy->reveal(),
            $this->viewerCodesInterfaceProphecy->reveal(),
            $this->viewerCodeActivityInterfaceProphecy->reveal(),
            $this->iapRepositoryProphecy->reveal(),
            $this->resolveActorProphecy->reveal(),
            $this->getAttorneyStatusProphecy->reveal(),
            $this->isValidLpaProphecy->reveal(),
            $this->getTrustCorporationStatusProphecy->reveal(),
            $this->loggerProphecy->reveal(),
        );
    }

    #[Test]
    public function can_get_by_id(): void
    {
        $testUid = '700012349874';

        $lpaResponse = new Lpa(
            new SiriusLpa(
                [
                    'attorneys'         => [
                        ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                        ['id' => 2, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false],
                        ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                        ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
                        ['id' => 5, 'systemStatus' => true],
                    ],
                    'trustCorporations' => [
                        new SiriusPerson(
                            [
                             'id'           => 6,
                             'companyName'  => 'XYZ Ltd',
                             'systemStatus' => true,
                            ],
                            $this->loggerProphecy->reveal(),
                        ),
                    ],
                ],
                $this->loggerProphecy->reveal(),
            ),
            new DateTime()
        );

        $expectedLpaResponse = new Lpa(
            new SiriusLpa(
                [
                    'attorneys'         => [
                        ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                        ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                        ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
                    ],
                    'trustCorporations' => [
                        new SiriusPerson(
                            [
                                'id'           => 6,
                                'companyName'  => 'XYZ Ltd',
                                'systemStatus' => true,
                            ],
                            $this->loggerProphecy->reveal(),
                        ),
                    ],
                ],
                $this->loggerProphecy->reveal(),
            ),
            $lpaResponse->getLookupTime()
        );

        //---

        $service = $this->getLpaService();

        $this->lpasInterfaceProphecy->get($testUid)->willReturn($lpaResponse);

        $this->getAttorneyStatusProphecy
            ->__invoke(
                new SiriusPerson(
                    ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                    $this->loggerProphecy->reveal(),
                )
            )
            ->willReturn(AttorneyStatus::ACTIVE_ATTORNEY);

        $this->getAttorneyStatusProphecy
            ->__invoke(
                new SiriusPerson(
                    ['id' => 2, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false],
                    $this->loggerProphecy->reveal(),
                )
            )
            ->willReturn(AttorneyStatus::INACTIVE_ATTORNEY);

        $this->getAttorneyStatusProphecy
            ->__invoke(
                new SiriusPerson(
                    ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                    $this->loggerProphecy->reveal(),
                )
            )
            ->willReturn(AttorneyStatus::ACTIVE_ATTORNEY);

        $this->getAttorneyStatusProphecy
            ->__invoke(
                new SiriusPerson(
                    ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
                    $this->loggerProphecy->reveal(),
                )
            )
            ->willReturn(AttorneyStatus::ACTIVE_ATTORNEY);

        $this->getAttorneyStatusProphecy
            ->__invoke(
                new SiriusPerson(
                    ['id' => 5, 'systemStatus' => true],
                    $this->loggerProphecy->reveal(),
                )
            )
            ->willReturn(AttorneyStatus::GHOST_ATTORNEY);

        $this->getTrustCorporationStatusProphecy
            ->__invoke(
                new SiriusPerson(
                    [
                        'id'           => 6,
                        'companyName'  => 'XYZ Ltd',
                        'systemStatus' => true,
                    ],
                    $this->loggerProphecy->reveal(),
                )
            )
            ->willReturn(TrustCorporationStatus::ACTIVE_TC);

        $this->getTrustCorporationStatusProphecy
            ->__invoke(
                new SiriusPerson(
                    [
                        'id'           => 7,
                        'companyName'  => 'ABC Ltd',
                        'systemStatus' => true,
                    ],
                    $this->loggerProphecy->reveal(),
                )
            )
            ->willReturn(TrustCorporationStatus::INACTIVE_TC);

        $result = $service->getByUid($testUid);

        $this->assertEquals($expectedLpaResponse, $result);
    }

    //-------------------------------------------------------------------------
    // Test getByUserLpaActorToken()

    private function init_valid_user_token_test($validState = true): stdClass
    {
        $t = new stdClass();

        $t->Token     = 'test-token';
        $t->UserId    = 'test-user-id';
        $t->SiriusUid = 'test-sirius-uid';
        $t->ActorId   = '1';
        $t->Lpa       = new Lpa(
            new SiriusLpa(
                [
                    'uId'               => $t->SiriusUid,
                    'status'            => 'Registered',
                    'attorneys'         => [
                        [
                            'id'           => $t->ActorId,
                            'firstname'    => 'Test',
                            'surname'      => 'Test',
                            'systemStatus' => true,
                        ],
                    ],
                    'trustCorporations' => [],
                    'activeAttorneys'   => [
                        [
                            'id'           => 1,
                            'firstname'    => 'Test',
                            'surname'      => 'Test',
                            'systemStatus' => true,
                        ],
                    ],
                    'inactiveAttorneys' => [],
                ],
                $this->loggerProphecy->reveal(),
            ),
            new DateTime()
        );

        $this->userLpaActorMapInterfaceProphecy->get($t->Token)->willReturn([
            'Id'        => $t->Token,
            'UserId'    => $t->UserId,
            'SiriusUid' => $t->SiriusUid,
            'ActorId'   => $t->ActorId,
        ]);

        $this->lpasInterfaceProphecy->get($t->SiriusUid)->willReturn($t->Lpa);

        // resolves LPA actor as primary attorney
        $this->resolveActorProphecy
            ->__invoke($t->Lpa->getData(), $t->ActorId)
            ->willReturn(
                new LpaActor(
                    [
                        'id'           => $t->ActorId,
                        'firstname'    => 'Test',
                        'surname'      => 'Test',
                        'systemStatus' => true,
                    ],
                    ActorType::ATTORNEY
                ),
            );

        // check valid lpa
        $this->isValidLpaProphecy->__invoke(
            $t->Lpa->getData()
        )->willReturn($validState);

        // attorney status is active
        $this->getAttorneyStatusProphecy->__invoke(
            new SiriusPerson(
                [
                'id'           => $t->ActorId,
                'firstname'    => 'Test',
                'surname'      => 'Test',
                'systemStatus' => true,
                ],
                $this->loggerProphecy->reveal(),
            )
        )->willReturn(AttorneyStatus::ACTIVE_ATTORNEY);

        return $t;
    }

    //-------------------------------------------------------------------------
    // Test getByUserLpaActorToken()

    private function init_valid_user_token_active_and_inactive_actor(): stdClass
    {
        $t = new stdClass();

        $t->Token     = 'test-token';
        $t->UserId    = 'test-user-id';
        $t->SiriusUid = 'test-sirius-uid';
        $t->ActorId   = 1;
        $t->Lpa       = new Lpa(
            new SiriusLpa(
                [
                    'uId'               => $t->SiriusUid,
                    'status'            => 'Registered',
                    'attorneys'         => [
                        [
                            'id'           => $t->ActorId,
                            'firstname'    => 'Test',
                            'surname'      => 'Test',
                            'systemStatus' => true,
                        ],
                        [
                            'id'           => 2,
                            'firstname'    => 'Test',
                            'surname'      => 'Test',
                            'systemStatus' => false,
                        ],
                    ],
                    'trustCorporations' => [],
                    'activeAttorneys'   => [
                        [
                            'id'           => 1,
                            'firstname'    => 'Test',
                            'surname'      => 'Test',
                            'systemStatus' => true,
                        ],
                    ],
                    'inactiveAttorneys' => [
                        [
                            'id'           => 2,
                            'firstname'    => 'Test',
                            'surname'      => 'Test',
                            'systemStatus' => false,
                        ],
                    ],
                ],
                $this->loggerProphecy->reveal(),
            ),
            new DateTime(),
        );

        $this->userLpaActorMapInterfaceProphecy->get($t->Token)
            ->willReturn([
                'Id'        => $t->Token,
                'UserId'    => $t->UserId,
                'SiriusUid' => $t->SiriusUid,
                'ActorId'   => $t->ActorId,
            ]);

        $this->lpasInterfaceProphecy->get($t->SiriusUid)->willReturn($t->Lpa);

        // resolves LPA actor as primary attorney
        $this->resolveActorProphecy
            ->__invoke($t->Lpa->getData(), (string) $t->ActorId)
            ->willReturn(
                new LpaActor(
                    [
                        'id'           => $t->ActorId,
                        'firstname'    => 'Test',
                        'surname'      => 'Test',
                        'systemStatus' => true,
                    ],
                    ActorType::ATTORNEY
                )
            );

        // check valid lpa
        $this->isValidLpaProphecy->__invoke(
            $t->Lpa->getData()
        )->willReturn(true);

        // attorney status is active
        $this->getAttorneyStatusProphecy->__invoke(
            new SiriusPerson(
                [
                    'id'           => $t->ActorId,
                    'firstname'    => 'Test',
                    'surname'      => 'Test',
                    'systemStatus' => true,
                ],
                $this->loggerProphecy->reveal(),
            )
        )->willReturn(AttorneyStatus::ACTIVE_ATTORNEY);

        $this->getAttorneyStatusProphecy->__invoke(
            new SiriusPerson(
                [
                    'id'           => 2,
                    'firstname'    => 'Test',
                    'surname'      => 'Test',
                    'systemStatus' => false,
                ],
                $this->loggerProphecy->reveal(),
            )
        )->willReturn(AttorneyStatus::INACTIVE_ATTORNEY);

        return $t;
    }

    #[Test]
    public function can_get_by_user_token(): void
    {
        $t = $this->init_valid_user_token_test();

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, (string) $t->UserId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user-lpa-actor-token', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('actor', $result);
        $this->assertArrayHasKey('lpa', $result);

        $this->assertEquals($t->Token, $result['user-lpa-actor-token']);
        $this->assertEquals($t->Lpa->getLookupTime()->getTimestamp(), strtotime($result['date']));
        $this->assertEquals(
            new LpaActor(
                [
                    'id'           => $t->ActorId,
                    'firstname'    => 'Test',
                    'surname'      => 'Test',
                    'systemStatus' => true,
                ],
                ActorType::ATTORNEY
            ),
            $result['actor'],
        );
        $this->assertEquals(
            new SiriusLpa(
                [
                    'uId'               => $t->SiriusUid,
                    'status'            => 'Registered',
                    'attorneys'         => [
                        [
                            'id'           => $t->ActorId,
                            'firstname'    => 'Test',
                            'surname'      => 'Test',
                            'systemStatus' => true,
                        ],
                    ],
                    'trustCorporations' => [],
                    'activeAttorneys'   => [
                        0 => [
                            'id'           => $t->ActorId,
                            'firstname'    => 'Test',
                            'surname'      => 'Test',
                            'systemStatus' => true,
                        ],
                    ],
                    'inactiveAttorneys' => [],
                ],
                $this->loggerProphecy->reveal(),
            ),
            $result['lpa']
        );
    }

    #[Test]
    public function can_get_by_user_token_with_an_active_and_inactive_actor(): void
    {
        $t = $this->init_valid_user_token_active_and_inactive_actor();

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, (string) $t->UserId);

        $this->assertNotNull($result);
        $this->assertNotNull($result['actor']);
    }

    #[Test]
    public function can_get_by_user_token_with_an_inactive_actor(): void
    {
        $t = $this->init_valid_user_token_active_and_inactive_actor();

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, (string) $t->UserId);

        $this->assertNotNull($result);
        $this->assertNotNull($result['actor']);
    }

    #[Test]
    public function cannot_get_by_user_token_with_invalid_userid(): void
    {
        $t = $this->init_valid_user_token_test();

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, (string) 123);

        $this->assertNull($result);
    }

    #[Test]
    public function cannot_get_by_user_token_with_invalid_sirius_uid(): void
    {
        $t = $this->init_valid_user_token_test();

        // Don't return an LPA.
        $this->lpasInterfaceProphecy->get($t->SiriusUid)->willReturn(null);

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, (string) $t->UserId);

        $this->assertNull($result);
    }

    #[Test]
    public function cannot_get_by_user_token_when_not_valid_lpa(): void
    {
        $t = $this->init_valid_user_token_test(false);

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, (string) $t->UserId);

        $this->assertEmpty($result);
    }

    //-------------------------------------------------------------------------
    // Test getAllForUser()

    private function init_valid_get_all_users(bool $includeActivated)
    {
        $t = new stdClass();

        $t->UserId = 'test-user-id';

        $t->mapResults = [
            [
                'Id'        => 'token-1',
                'SiriusUid' => 'uid-1',
                'ActorId'   => 1,
                'Added'     => new DateTime('now'),
            ],
            [
                'Id'        => 'token-2',
                'SiriusUid' => 'uid-2',
                'ActorId'   => 2,
                'Added'     => new DateTime('now'),
            ],
            [
                'Id'         => 'token-3',
                'SiriusUid'  => 'uid-3',
                'ActorId'    => 3,
                'ActivateBy' => (new DateTime('now'))->add(new DateInterval('P1Y'))->getTimeStamp(),
                'Added'      => new DateTime('now'),
            ],
            [
                'Id'      => 'token-4',
                'LpaUid'  => 'M-uid-1',
                'ActorId' => 4,
                'Added'   => new DateTime('now'),
            ],
        ];

        $t->lpaResults = [
            'uid-1' => new Lpa(
                new SiriusLpa(
                    [
                        'uId'    => 'uid-1',
                        'status' => 'Registered',
                    ],
                    $this->loggerProphecy->reveal(),
                ),
                new DateTime(),
            ),
            'uid-2' => new Lpa(
                new SiriusLpa(
                    [
                        'uId'    => 'uid-2',
                        'status' => 'Registered',
                    ],
                    $this->loggerProphecy->reveal(),
                ),
                new DateTime()
            ),
        ];

        $this->userLpaActorMapInterfaceProphecy->getByUserId($t->UserId)->willReturn($t->mapResults);

        $this->lpasInterfaceProphecy->lookup(['uid-1', 'uid-2'])->willReturn($t->lpaResults);

        // check valid lpa
        $this->isValidLpaProphecy->__invoke(
            $t->lpaResults['uid-1']->getData()
        )->willReturn(true);

        // check valid lpa
        $this->isValidLpaProphecy->__invoke(
            $t->lpaResults['uid-2']->getData()
        )->willReturn(true);

        if ($includeActivated) {
            $t->lpaResults['uid-3'] = new Lpa(
                new SiriusLpa(
                    [
                       'status' => 'Registered',
                        'uId'   => 'uid-3',
                    ],
                    $this->loggerProphecy->reveal(),
                ),
                new DateTime(),
            );
            $this->lpasInterfaceProphecy->lookup(['uid-1', 'uid-2', 'uid-3'])->willReturn($t->lpaResults);

            // check valid lpa
            $this->isValidLpaProphecy->__invoke(
                $t->lpaResults['uid-3']->getData()
            )->willReturn(true);
        }

        return $t;
    }

    #[Test]
    public function can_get_all_lpas_for_user(): void
    {
        $t = $this->init_valid_get_all_users(false);

        $service = $this->getLpaService();

        $result = $service->getAllActiveForUser($t->UserId);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $result = array_pop($result);

        //---

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user-lpa-actor-token', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('actor', $result);
        $this->assertArrayHasKey('lpa', $result);

        $lpa = array_pop($t->lpaResults);
        array_pop($t->mapResults); //discard the first modernise lpa (No Sirius uid)
        array_pop($t->mapResults); //discard the second lpa with TTL
        $map = array_pop($t->mapResults);

        $this->assertEquals($map['Id'], $result['user-lpa-actor-token']);
        $this->assertEquals($lpa->getLookupTime()->getTimestamp(), strtotime($result['date']));
        $this->assertEquals(null, $result['actor']);    // We're expecting no actor to have been found
        $this->assertEquals($lpa->getData(), $result['lpa']);
    }

    #[Test]
    public function can_get_all_lpas_for_user_including_not_activated(): void
    {
        $t = $this->init_valid_get_all_users(true);

        $service = $this->getLpaService();

        $result = $service->getAllForUser($t->UserId);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $result = array_pop($result);

        //---

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user-lpa-actor-token', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('actor', $result);
        $this->assertArrayHasKey('lpa', $result);

        $lpa = array_pop($t->lpaResults);
        array_pop($t->mapResults); //discard the first modernise lpa (No Sirius uid)
        $map = array_pop($t->mapResults);

        $this->assertEquals($map['Id'], $result['user-lpa-actor-token']);
        $this->assertEquals($lpa->getLookupTime()->getTimestamp(), strtotime($result['date']));
        $this->assertEquals(null, $result['actor']);    // We're expecting no actor to have been found
        $this->assertEquals($lpa->getData(), $result['lpa']);
    }

    #[Test]
    public function cannot_get_all_lpas_for_user_when_no_maps_found(): void
    {
        $t = $this->init_valid_get_all_users(false);

        $service = $this->getLpaService();

        $this->userLpaActorMapInterfaceProphecy->getByUserId($t->UserId)->willReturn([]);

        $result = $service->getAllActiveForUser($t->UserId);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    //-------------------------------------------------------------------------
    // Test getByViewerCode()

    private function init_valid_get_by_viewer_account()
    {
        $t = new stdClass();

        $t->ViewerCode   = 'test-viewer-code';
        $t->Organisation = 'test-organisation';
        $t->DonorSurname = 'test-donor-surename';
        $t->SiriusUid    = '700000000001';
        $t->Expires      = new DateTime('+1 hour');

        $t->Lpa = new Lpa(
            new SiriusLpa(
                [
                    'uId'                        => $t->SiriusUid,
                    'donor'                      => [
                        'surname' => $t->DonorSurname,
                    ],
                    'attorneys'                  => [],
                    'trustCorporations'          => [],
                    'activeAttorneys'            => [],
                    'inactiveAttorneys'          => [],
                    'applicationHasGuidance'     => true,
                    'applicationHasRestrictions' => true,
                ],
                $this->loggerProphecy->reveal(),
            ),
            new DateTime()
        );


        $this->viewerCodesInterfaceProphecy->get($t->ViewerCode)->willReturn([
            'ViewerCode'   => $t->ViewerCode,
            'SiriusUid'    => $t->SiriusUid,
            'Expires'      => $t->Expires,
            'Organisation' => $t->Organisation,
        ]);

        $this->lpasInterfaceProphecy->get($t->SiriusUid)->willReturn($t->Lpa);

        $this->iapRepositoryProphecy
            ->getInstructionsAndPreferencesImages((int) $t->SiriusUid)
            ->willReturn(
                new InstructionsAndPreferencesImages(
                    (int) $t->SiriusUid,
                    InstructionsAndPreferencesImagesResult::COLLECTION_COMPLETE,
                    [],
                )
            );

        return $t;
    }

    #[Test]
    public function can_get_lpa_by_viewer_code_no_logging(): void
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        //---

        // This should NOT be called when organisation is null.
        $this->viewerCodeActivityInterfaceProphecy->recordSuccessfulLookupActivity(
            Argument::any(),
        )->shouldNotBeCalled();

        //---

        $result = $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('expires', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('organisation', $result);
        $this->assertArrayHasKey('lpa', $result);

        $this->assertEquals($t->Lpa->getLookupTime()->getTimestamp(), strtotime($result['date']));
        $this->assertEquals($t->Expires->getTimestamp(), strtotime($result['expires']));

        $this->assertEquals($t->Organisation, $result['organisation']);
        $this->assertEquals($t->Lpa->getData(), $result['lpa']);
    }

    #[Test]
    public function can_get_lpa_by_viewer_code_with_logging(): void
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        //---

        // This should be called when organisation is provided
        $this->viewerCodeActivityInterfaceProphecy->recordSuccessfulLookupActivity(
            $t->ViewerCode,
            $t->Organisation
        )->shouldBeCalled();

        //---

        $result = $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, $t->Organisation);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('expires', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('organisation', $result);
        $this->assertArrayHasKey('lpa', $result);

        $this->assertEquals($t->Lpa->getLookupTime()->getTimestamp(), strtotime($result['date']));
        $this->assertEquals($t->Expires->getTimestamp(), strtotime($result['expires']));

        $this->assertEquals($t->Organisation, $result['organisation']);
        $this->assertEquals($t->Lpa->getData(), $result['lpa']);
    }

    #[Test]
    public function cannot_get_lpa_by_missing_viewer_code(): void
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        // Change this to return null
        $this->viewerCodesInterfaceProphecy->get($t->ViewerCode)->willReturn(null)->shouldBeCalled();

        $this->expectException(NotFoundException::class);
        $result = $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);
    }

    #[Test]
    public function cannot_get_missing_lpa_by_viewer_code(): void
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        // Change this to return null
        $this->lpasInterfaceProphecy->get($t->SiriusUid)->willReturn(null)->shouldBeCalled();

        $this->expectException(NotFoundException::class);
        $result = $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);
    }

    #[Test]
    public function cannot_get_lpa_with_invalid_donor_by_viewer_code(): void
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        $this->expectException(NotFoundException::class);
        $result = $service->getByViewerCode($t->ViewerCode, 'different-donor-name', null);
    }

    #[Test]
    public function cannot_get_lpa_by_viewer_code_with_missing_expiry(): void
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        //---

        $this->viewerCodesInterfaceProphecy->get($t->ViewerCode)->willReturn([
            'ViewerCode' => $t->ViewerCode,
            'SiriusUid'  => $t->SiriusUid,
            //'Expires' => $t->Expires,             <-- Expires is removed
            'Organisation' => $t->Organisation,
        ]);

        //---

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Missing code expiry data in Dynamo response');

        $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);
    }

    #[Test]
    public function cannot_get_lpa_by_viewer_code_with_cancelled(): void
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        //---

        $this->viewerCodesInterfaceProphecy->get($t->ViewerCode)->willReturn([
            'ViewerCode'   => $t->ViewerCode,
            'SiriusUid'    => $t->SiriusUid,
            'Expires'      => new DateTime('1 hour'),
            'Cancelled'    => true,
            'Organisation' => $t->Organisation,
        ]);

        //---

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Share code cancelled');

        $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);
    }

    #[Test]
    public function cannot_get_lpa_by_viewer_code_with_expired_expiry(): void
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        //---

        $this->viewerCodesInterfaceProphecy->get($t->ViewerCode)->willReturn([
            'ViewerCode'   => $t->ViewerCode,
            'SiriusUid'    => $t->SiriusUid,
            'Expires'      => new DateTime('-1 hour'),
            'Organisation' => $t->Organisation,
        ]);

        //---

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Share code expired');

        $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);
    }

    #[Test]
    public function lpa_fetched_by_viewer_code_contains_instructions_and_preferences_data(): void
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        $result = $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('iap', $result);

        $this->assertEquals((int) $t->SiriusUid, $result['iap']->uId);
    }

    #[Test]
    public function will_return_empty_lpa_array_when_status_invalid(): void
    {
        $t = $this->init_valid_user_token_test();

        // check valid lpa and returns false
        $this->isValidLpaProphecy->__invoke(
            $t->Lpa->getData()
        )->willReturn(false);

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, $t->SiriusUid);

        $this->assertEmpty($result);
    }
}
