<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Entity\Casters\CastSingleDonor;
use App\Entity\Casters\CastToCaseSubtype;
use App\Entity\Casters\CastToLifeSustainingTreatment;
use App\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use App\Entity\Casters\DateToStringSerializer;
use App\Entity\Casters\ExtractAddressLine1FromDataStore;
use App\Entity\Casters\ExtractCountryFromDataStore;
use App\Entity\Casters\ExtractTownFromDataStore;
use App\Entity\DataStore\DataStoreDonor;
use App\Entity\Person;
use EventSauce\ObjectHydrator\ObjectMapper;
use Laminas\Hydrator\HydratorInterface;
use App\DataAccess\{Repository\InstructionsAndPreferencesImagesInterface,
    Repository\LpasInterface,
    Repository\UserLpaActorMapInterface,
    Repository\ViewerCodeActivityInterface,
    Repository\ViewerCodesInterface};
use App\DataAccess\Repository\Response\{InstructionsAndPreferencesImages, InstructionsAndPreferencesImagesResult, Lpa};
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\{GetAttorneyStatus,
    GetTrustCorporationStatus,
    IsValidLpa,
    LpaDataFormatter,
    LpaService,
    ResolveActor};
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

class LpaServiceTest extends TestCase
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
    private LpaDataFormatter|ObjectProphecy $lpaDataFormatter;
    private ObjectMapper|ObjectProphecy $hydrator;

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
        $this->lpaDataFormatter                    = $this->prophesize(LpaDataFormatter::class);
        $this->loggerProphecy                      = $this->prophesize(LoggerInterface::class);
        $this->hydrator                            = $this->prophesize(ObjectMapper::class);
    }

    private function getLpaService(): LpaService
    {
        return new LpaService(
            $this->userLpaActorMapInterfaceProphecy->reveal(),
            $this->lpasInterfaceProphecy->reveal(),
            $this->viewerCodesInterfaceProphecy->reveal(),
            $this->viewerCodeActivityInterfaceProphecy->reveal(),
            $this->iapRepositoryProphecy->reveal(),
            $this->resolveActorProphecy->reveal(),
            $this->getAttorneyStatusProphecy->reveal(),
            $this->isValidLpaProphecy->reveal(),
            $this->getTrustCorporationStatusProphecy->reveal(),
            $this->featureEnabledProphecy->reveal(),
            $this->loggerProphecy->reveal(),
        );
    }

    #[Test]
    public function can_get_by_id(): void
    {
        $testUid = '700012349874';

        $lpaResponse = new Lpa([
            'attorneys'         => [
                ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                ['id' => 2, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false],
                ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
                ['id' => 5, 'systemStatus' => true],
            ],
            'trustCorporations' => [
                ['id' => 6, 'companyName' => 'XYZ Ltd', 'systemStatus' => true],
            ],
        ], new DateTime());

        $expectedLpaResponse = new Lpa([
            'attorneys'          => [
                ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                ['id' => 2, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false],
                ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
                ['id' => 5, 'systemStatus' => true],
            ],
            'original_attorneys' => [
                ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                ['id' => 2, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false],
                ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
                ['id' => 5, 'systemStatus' => true],
            ],
            'trustCorporations'  => [
                ['id' => 6, 'companyName' => 'XYZ Ltd', 'systemStatus' => true],
            ],
            'inactiveAttorneys'  => [
                ['id' => 2, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false],
            ],
            'activeAttorneys'    => [
                ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
            ],
        ], $lpaResponse->getLookupTime());

        //---

        $service = $this->getLpaService();

        $this->lpasInterfaceProphecy->get($testUid)->willReturn($lpaResponse);

        $this->getAttorneyStatusProphecy
            ->__invoke(['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true])
            ->willReturn(0);

        $this->getAttorneyStatusProphecy
            ->__invoke(['id' => 2, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false])
            ->willReturn(2);

        $this->getAttorneyStatusProphecy
            ->__invoke(['id' => 3, 'firstname' => 'A', 'systemStatus' => true])
            ->willReturn(0);

        $this->getAttorneyStatusProphecy
            ->__invoke(['id' => 4, 'surname' => 'B', 'systemStatus' => true])
            ->willReturn(0);

        $this->getAttorneyStatusProphecy
            ->__invoke(['id' => 5, 'systemStatus' => true])
            ->willReturn(1);

        $this->getTrustCorporationStatusProphecy
            ->__invoke(['id' => 6, 'companyName' => 'XYZ Ltd', 'systemStatus' => true])
            ->willReturn(0);

        $this->getTrustCorporationStatusProphecy
            ->__invoke(['id' => 7, 'companyName' => 'ABC Ltd', 'systemStatus' => true])
            ->willReturn(2);

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
        $t->ActorId   = 1;
        $t->Lpa       = new Lpa(
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
            ->__invoke([
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
            ], $t->ActorId)
            ->willReturn([
                'type'    => 'primary-attorney',
                'details' => [
                    'id'           => $t->ActorId,
                    'firstname'    => 'Test',
                    'surname'      => 'Test',
                    'systemStatus' => true,
                ],
            ]);

        // check valid lpa
        $this->isValidLpaProphecy->__invoke(
            $t->Lpa->getData()
        )->willReturn($validState);

        // attorney status is active
        $this->getAttorneyStatusProphecy->__invoke([
            'id'           => $t->ActorId,
            'firstname'    => 'Test',
            'surname'      => 'Test',
            'systemStatus' => true,
        ])->willReturn(0);

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
            new DateTime()
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
            ->__invoke([
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
            ], $t->ActorId)
            ->willReturn([
                'type'    => 'primary-attorney',
                'details' => [
                    'id'           => $t->ActorId,
                    'firstname'    => 'Test',
                    'surname'      => 'Test',
                    'systemStatus' => true,
                ],
            ]);

        // check valid lpa
        $this->isValidLpaProphecy->__invoke(
            $t->Lpa->getData()
        )->willReturn(true);

        // attorney status is active
        $this->getAttorneyStatusProphecy->__invoke([
            'id'           => $t->ActorId,
            'firstname'    => 'Test',
            'surname'      => 'Test',
            'systemStatus' => true,
        ])->willReturn(0);

        $this->getAttorneyStatusProphecy->__invoke([
            'id'           => 2,
            'firstname'    => 'Test',
            'surname'      => 'Test',
            'systemStatus' => false,
        ])->willReturn(2);

        return $t;
    }

    //-------------------------------------------------------------------------
    // Test getByUserLpaActorToken()

    private function init_valid_user_token_inactive_actor(): stdClass
    {
        $t = new stdClass();

        $t->Token     = 'test-token';
        $t->UserId    = 'test-user-id';
        $t->SiriusUid = 'test-sirius-uid';
        $t->ActorId   = 1;
        $t->Lpa       = new Lpa(
            [
                'uId'               => $t->SiriusUid,
                'status'            => 'Registered',
                'attorneys'         => [
                    [
                        'id'           => 1,
                        'firstname'    => 'Test',
                        'surname'      => 'Test',
                        'systemStatus' => false,
                    ],
                ],
                'trustCorporations' => [],
                'activeAttorneys'   => [],
                'inactiveAttorneys' => [
                    [
                        'id'           => 1,
                        'firstname'    => 'Test',
                        'surname'      => 'Test',
                        'systemStatus' => false,
                    ],
                ],
            ],
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
            ->__invoke([
                'uId'               => $t->SiriusUid,
                'status'            => 'Registered',
                'attorneys'         => [
                    [
                        'id'           => 1,
                        'firstname'    => 'Test',
                        'surname'      => 'Test',
                        'systemStatus' => false,
                    ],
                ],
                'trustCorporations' => [],
                'activeAttorneys'   => [],
                'inactiveAttorneys' => [
                    [
                        'id'           => 1,
                        'firstname'    => 'Test',
                        'surname'      => 'Test',
                        'systemStatus' => false,
                    ],
                ],
            ], $t->ActorId)
            ->willReturn([
                'type'    => 'primary-attorney',
                'details' => [
                    'id'           => $t->ActorId,
                    'firstname'    => 'Test',
                    'surname'      => 'Test',
                    'systemStatus' => false,
                ],
            ]);

        // check valid lpa
        $this->isValidLpaProphecy->__invoke(
            $t->Lpa->getData()
        )->willReturn(true);

        $this->getAttorneyStatusProphecy->__invoke([
            'id'           => 1,
            'firstname'    => 'Test',
            'surname'      => 'Test',
            'systemStatus' => false,
        ])->willReturn(2);

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
        $this->assertEquals([
            'type'    => 'primary-attorney',
            'details' => [
                'id'           => $t->ActorId,
                'firstname'    => 'Test',
                'surname'      => 'Test',
                'systemStatus' => true,
            ],
        ], $result['actor']);
        $this->assertEquals([
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
        ], $result['lpa']);
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
        ];

        $t->lpaResults = [
            'uid-1' => new Lpa([
                'uId'    => 'uid-1',
                'status' => 'Registered',
            ], new DateTime()),
            'uid-2' => new Lpa([
                'uId'    => 'uid-2',
                'status' => 'Registered',
            ], new DateTime()),
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
                [
                   'status' => 'Registered',
                    'uId'    => 'uid-3',
                ], new DateTime()
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

        $result = $service->getAllForUser($t->UserId);

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
        array_pop($t->mapResults); //discard the first lpa with TTL
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

        $result = $service->getAllLpasAndRequestsForUser($t->UserId);

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

        $result = $service->getAllForUser($t->UserId);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    private function init_valid_get_all_users_with_linked()
    {
        $t = new stdClass();

        $t->UserId = 'test-user-id';

        $t->mapResults = [
            [
                'Id'        => 'token-1',
                'SiriusUid' => 'uid-1',
                'ActorId'   => 1,
                'Added'     => new DateTime('today'),
            ],
            [
                'Id'        => 'token-2',
                'SiriusUid' => 'uid-2',
                'ActorId'   => 2,
                'Added'     => new DateTime('today'),
            ],
        ];

        $lpa1 = new Lpa([
            'uId'    => 'uid-1',
            'status' => 'Registered',
            'donor'  => [
                'linked' => [['id' => 1, 'uId' => 'person-1']],
            ],
        ], new DateTime());

        $lpa2 = new Lpa([
            'uId'    => 'uid-2',
            'status' => 'Registered',
            'donor'  => [
                'linked' => [['id' => 2, 'uId' => 'person-2']],
            ],
        ], new DateTime());

        $t->lpaResults = [
            'uid-1' => $lpa1,
            'uid-2' => $lpa2,
        ];

        $this->userLpaActorMapInterfaceProphecy->getByUserId($t->UserId)->willReturn($t->mapResults);

        $this->lpasInterfaceProphecy->lookup(array_column($t->mapResults, 'SiriusUid'))->willReturn($t->lpaResults);

        // resolves the donor as the actor for LPA 1
        $this->resolveActorProphecy->__invoke(
            $lpa1->getData(),
            1
        )->willReturn([
            'type'    => 'donor',
            'details' => [
                'linked' => [['id' => 1, 'uId' => 'person-1']],
            ],
        ]);

        // resolves the donor as the actor for LPA 2
        $this->resolveActorProphecy->__invoke(
            $lpa2->getData(),
            2
        )->willReturn([
            'type'    => 'donor',
            'details' => [
                'linked' => [['id' => 2, 'uId' => 'person-2']],
            ],
        ]);

        //check valid lpa
        $this->isValidLpaProphecy->__invoke(
            $lpa1->getData(),
        )->willReturn(true);

        $this->isValidLpaProphecy->__invoke(
            $lpa2->getData(),
        )->willReturn(true);

        return $t;
    }

    #[Test]
    public function can_get_all_lpas_for_user_when_linked_donor(): void
    {
        $t = $this->init_valid_get_all_users_with_linked();

        $this->userLpaActorMapInterfaceProphecy->getByUserId($t->UserId)->willReturn($t->mapResults);

        $this->lpasInterfaceProphecy->lookup(array_column($t->mapResults, 'SiriusUid'))->willReturn($t->lpaResults);

        $service = $this->getLpaService();

        $result = $service->getAllForUser($t->UserId);

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
        $map = array_pop($t->mapResults);

        $this->assertEquals($map['Id'], $result['user-lpa-actor-token']);
        $this->assertEquals($lpa->getLookupTime()->getTimestamp(), strtotime($result['date']));
        $this->assertEquals(['type' => 'donor', 'details' => $lpa->getData()['donor']], $result['actor']);
        $this->assertEquals($lpa->getData(), $result['lpa']);
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

        $t->Lpa = new Lpa([
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
        ], new DateTime());


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

        $this->featureEnabledProphecy
            ->__invoke('instructions_and_preferences')
            ->willReturn(false);

        $service = $this->getLpaService();

        //---

        // This should NOT be called when logging = false.
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

        $this->featureEnabledProphecy
            ->__invoke('instructions_and_preferences')
            ->willReturn(false);

        $service = $this->getLpaService();

        //---

        // This should be called when logging = true.
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

        $result = $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);

        $this->assertNull($result);
    }

    #[Test]
    public function cannot_get_missing_lpa_by_viewer_code(): void
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        // Change this to return null
        $this->lpasInterfaceProphecy->get($t->SiriusUid)->willReturn(null)->shouldBeCalled();

        $result = $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);

        $this->assertNull($result);
    }

    #[Test]
    public function cannot_get_lpa_with_invalid_donor_by_viewer_code(): void
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        $result = $service->getByViewerCode($t->ViewerCode, 'different-donor-name', null);

        $this->assertNull($result);
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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("'Expires' field missing or invalid.");

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

        $this->featureEnabledProphecy
            ->__invoke('instructions_and_preferences')
            ->willReturn(true);

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

    #[Test]
    public function can_get_transformed_data_store_lpa_by_id(): void
    {
        $donorObj = new Person(
            'Feeg Bundlaaaa',
            '74 Cloop Close',
            '',
            '',
            'GB',
            'Town',
            '',
            'Mahhhhhhhhh',
            '',
            new \DateTimeImmutable('1970-01-24'),
            'nobody@not_a_real_domain',
            'Feeg',
            '',
            'Bundlaaaa',
            '',
            ''
        );

        $lpaResponse = [
                'lpaType' => 'personal-welfare',
                'channel' => 'online',
                'donor'   => [
                    'uid' => 'eda719db-8880-4dda-8c5d-bb9ea12c236f',
                        'firstNames' => 'Feeg',
                        'lastName' => 'Bundlaaaa',
                        'address' => [
                            'line1' => '74 Cloob Close',
                            'town' => 'Mahhhhhhhhhh',
                            'country' => 'GB',
                        ],
                        'dateOfBirth' => '1970-01-24',
                        'email' => 'nobody@not.a.real.domain',
                        'contactLanguagePreference' => 'en',
                    ],
                'attorneys' => [
                    [
                        'uid' => '9ac5cb7c-fc75-40c7-8e53-059f36dbbe3d',
                        'firstNames' => 'Herman',
                        'lastName' => 'Seakrest',
                        'address' => [
                            'line1' => '81 NighOnTimeWeBuiltIt Street',
                            'town' => 'Mahhhhhhhhhh',
                            'country' => 'GB',
                        ],
                        'dateOfBirth' => '1982-07-24',
                        'status' => 'active',
                        'channel' => 'paper',
                    ],
                ],
                'trustCorporations' => [
                    [
                        'uid' => '1d95993a-ffbb-484c-b2fe-f4cca51801da',
                        'name' => 'Trust us Corp.',
                        'companyNumber' => '666123321',
                        'address' => [
                            'line1' => '103 Line 1',
                            'town' => 'Town',
                            'country' => 'GB',
                        ],
                        'status' => 'active',
                        'channel' => 'paper',
                    ],
                ],
                'certificateProvider' => [
                    'uid' => '6808960d-12cf-47c5-a2bc-3177deb8599c',
                    'firstNames' => 'Vone',
                    'lastName' => 'Spust',
                    'address' => [
                        'line1' => '122111 Zonnington Way',
                        'town' => 'Mahhhhhhhhhh',
                        'country' => 'GB',
                    ],
                    'channel' => 'online',
                    'email' => 'a@example.com',
                    'phone' => '070009000',
                ],
                'lifeSustainingTreatmentOption' => 'option-a',
                'signedAt' => '2024-01-10T23:00:00Z',
                'certificateProviderNotRelatedConfirmedAt' => '2024-01-11T22:00:00Z',
                'howAttorneysMakeDecisions' => 'jointly',
            ];

        $expectedLpaResponse = [
            'attorneyActDecisions' => [
                'name' => 'JOINTLY',
                'value' => 'jointly',
            ],
            'attorneys' => [
                [
                    'addressLine1' => '81 NighOnTimeWeBuiltIt Street',
                    'country' => 'GB',
                    'town' => 'Mahhhhhhhhh',
                    'dob' => [
                        'date' => '1982-07-24 00:00:00.000000',
                        'timezone_type' => 3,
                        'timezone' => 'UTC',
                    ],
                    'firstNames' => 'Herman',
                    'surName' => 'Seakrest',
                    'systemStatus' => 'active',
                ],
            ],
            'caseSubtype' => [
                'name' => 'PERSONAL_WELFARE',
                'value' => 'personal-welfare',
            ],
            'channel' => 'online',
            'donor' => [
                'addressLine1' => '74 Cloop Close',
                'country' => 'GB',
                'town' => 'Mahhhhhhhhh',
                'dob' => [
                    'date' => '1970-01-24 00:00:00.000000',
                    'timezone_type' => 3,
                    'timezone' => 'UTC',
                ],
                'email' => 'nobody@not_a_real_domain',
                'firstNames' => 'Feeg',
                'surName' => 'Bundlaaaa',
            ],
            'lifeSustainingTreatment' => [
                'name' => 'OPTION_A',
                'value' => 'option-a',
            ],
            'lpaDonorSignatureDate' => [
                'date' => '2021-01-10 23:00:00.000000',
                'timezone_type' => 2,
                'timezone' => 'Z',
            ],
            'trustCorporations' => [
                [
                    'name' => 'Trust us Corp.',
                    'addressLine1' => '103 Line 1',
                    'country' => 'GB',
                    'town' => 'Town',
                    'systemStatus' => 'active',
                    'companyName' => 'Trust us Corp.',
                ],
            ],
        ];

        $this->lpaDataFormatter->__invoke(
            $lpaResponse
        )->willReturn($expectedLpaResponse);

        $actualLpaResponse = ($this->lpaDataFormatter->reveal())($lpaResponse);

        $this->assertEquals($expectedLpaResponse, $actualLpaResponse);
    }

    #[Test]
    public function can_cast_single_donor(): void
    {

        $donor = [
            'uid' => 'eda719db-8880-4dda-8c5d-bb9ea12c236f',
            'firstNames' => 'Feeg',
            'lastName' => 'Bundlaaaa',
            'address' => [
                'line1' => '74 Cloob Close',
                'town' => 'Mahhhhhhhhhh',
                'country' => 'GB',
            ],
            'dateOfBirth' => '1970-01-24',
            'email' => 'nobody@not.a.real.domain',
            'contactLanguagePreference' => 'en',
        ];

        $datastoreDonor = new DataStoreDonor(
            null,
            null,
            '74 Cloop Close',
            null,
            null,
            'GB',
            null,
            null,
            'Mahhhhhhhhh',
            null,
            new \DateTimeImmutable('1970-01-24 00:00:00.000000'),
            'nobody@not_a_real_domain',
            null,
            'Feeg',
            'Bundlaaaa',
            null,
            null,
        );

        $castSingleDonor = new CastSingleDonor();

        $mockHydrator = $this->createMock(ObjectMapper::class);

        $result = $castSingleDonor->cast($donor, $mockHydrator);
    print_r($datastoreDonor);
    print_r($result);
        $this->assertEquals($datastoreDonor, $result);
    }

    #[Test]
    public function can_cast_case_subtype(): void
    {
        $caseSubType = 'personal-welfare';

        $expectedCaseSubType = 'hw';

        $castToSingleSubType = new CastToCaseSubtype();

        $mockHydrator = $this->createMock(ObjectMapper::class);

        $result = $castToSingleSubType->cast($caseSubType, $mockHydrator);

        $this->assertEquals($expectedCaseSubType, $result);
    }

    #[Test]
    public function can_cast_life_sustaining_treatment(): void
    {
        $lifeSustainingTreatment = 'option-a';

        $expectedLifeSustainingTreatment = 'option-a';

        $castToLifeSustainingTreatment = new CastToLifeSustainingTreatment();

        $mockHydrator = $this->createMock(ObjectMapper::class);

        $result = $castToLifeSustainingTreatment->cast($lifeSustainingTreatment, $mockHydrator);

        $this->assertEquals($expectedLifeSustainingTreatment, $result);
    }

    #[Test]
    public function can_when_lpa_can_be_used(): void
    {
        $whenTheLpaCanBeUsed = 'singular';

        $expectedWhenTheLpaCanBeUsed = 'singular';

        $castToWhenTheLpaCanBeUsed = new CastToWhenTheLpaCanBeUsed();

        $mockHydrator = $this->createMock(ObjectMapper::class);

        $result = $castToWhenTheLpaCanBeUsed->cast($whenTheLpaCanBeUsed, $mockHydrator);

        $this->assertEquals($expectedWhenTheLpaCanBeUsed, $result);
    }

    #[Test]
    public function can_date_to_string_serialised(): void
    {
        $date = new \DateTimeImmutable('22-12-1997');

        $expecteDate = '22-12-1997 00:00:00';

        $castDateToStringSerialize = new DateToStringSerializer();

        $mockHydrator = $this->createMock(ObjectMapper::class);

        $result = $castDateToStringSerialize->serialize($date, $mockHydrator);

        $this->assertEquals($expecteDate, $result);
    }

    #[Test]
    public function can_extract_town_from_datastore(): void
    {
        $address = [
            'line1' => '74 Cloob Close',
            'town' => 'Mahhhhhhhhhh',
            'country' => 'GB',
        ];

        $expectedTown = 'Mahhhhhhhhhh';

        $extractTownFromDataStore = new ExtractTownFromDataStore();

        $mockHydrator = $this->createMock(ObjectMapper::class);

        $result = $extractTownFromDataStore->cast($address, $mockHydrator);

        $this->assertEquals($expectedTown, $result);
    }

    #[Test]
    public function can_extract_country_from_datastore(): void
    {
        $address = [
            'line1' => '74 Cloob Close',
            'town' => 'Mahhhhhhhhhh',
            'country' => 'GB',
        ];

        $expectedCountry = 'GB';

        $extractCountryFromDataStore = new ExtractCountryFromDataStore();

        $mockHydrator = $this->createMock(ObjectMapper::class);

        $result = $extractCountryFromDataStore->cast($address, $mockHydrator);

        $this->assertEquals($expectedCountry, $result);
    }

    #[Test]
    public function can_extract_address_one_from_datastore(): void
    {
        $address = [
            'line1' => '74 Cloob Close',
            'town' => 'Mahhhhhhhhhh',
            'country' => 'GB',
        ];

        $expectedAddressOne = '74 Cloob Close';

        $extractAddressOneFromDataStore = new ExtractAddressLine1FromDataStore();

        $mockHydrator = $this->createMock(ObjectMapper::class);

        $result = $extractAddressOneFromDataStore->cast($address, $mockHydrator);

        $this->assertEquals($expectedAddressOne, $result);
    }
}
