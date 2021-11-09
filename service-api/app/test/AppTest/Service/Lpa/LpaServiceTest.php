<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\{ApiGateway\ActorCodes,
    Repository,
    Repository\UserLpaActorMapInterface,
    Repository\ViewerCodesInterface};
use App\DataAccess\Repository\Response\{Lpa};
use App\Service\Lpa\GetAttorneyStatus;
use App\Service\Lpa\IsValidLpa;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\ResolveActor;
use App\Service\ViewerCodes\ViewerCodeService;
use DateTime;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;

class LpaServiceTest extends TestCase
{
    /**
     * @var Repository\ViewerCodesInterface
     */
    private $viewerCodesInterfaceProphecy;

    /**
     * @var Repository\ViewerCodeActivityInterface
     */
    private $viewerCodeActivityInterfaceProphecy;

    /**
     * @var Repository\LpasInterface
     */
    private $lpasInterfaceProphecy;

    /**
     * @var Repository\UserLpaActorMapInterface
     */
    private $userLpaActorMapInterfaceProphecy;

    /**
     * @var LoggerInterface
     */
    private $loggerProphecy;

    /**
     * @var ActorCodes
     */
    public $actorCodesProphecy;

    /**
     * @var ResolveActor
     */
    private $resolveActorProphecy;

    /**
     * @var GetAttorneyStatus
     */
    private $getAttorneyStatusProphecy;

    /**
     * @var IsValidLpa
     */
    private $isValidLpaProphecy;

    public function setUp()
    {
        $this->viewerCodesInterfaceProphecy = $this->prophesize(Repository\ViewerCodesInterface::class);
        $this->viewerCodeActivityInterfaceProphecy = $this->prophesize(Repository\ViewerCodeActivityInterface::class);
        $this->lpasInterfaceProphecy = $this->prophesize(Repository\LpasInterface::class);
        $this->userLpaActorMapInterfaceProphecy = $this->prophesize(Repository\UserLpaActorMapInterface::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->actorCodesProphecy = $this->prophesize(ActorCodes::class);
        $this->resolveActorProphecy = $this->prophesize(ResolveActor::class);
        $this->getAttorneyStatusProphecy = $this->prophesize(GetAttorneyStatus::class);
        $this->isValidLpaProphecy = $this->prophesize(IsValidLpa::class);
    }

    //-------------------------------------------------------------------------
    // Test getByUid()

    private function getLpaService(): LpaService
    {
        return new LpaService(
            $this->viewerCodesInterfaceProphecy->reveal(),
            $this->viewerCodeActivityInterfaceProphecy->reveal(),
            $this->lpasInterfaceProphecy->reveal(),
            $this->userLpaActorMapInterfaceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
            $this->actorCodesProphecy->reveal(),
            $this->resolveActorProphecy->reveal(),
            $this->getAttorneyStatusProphecy->reveal(),
            $this->isValidLpaProphecy->reveal(),
        );
    }

    private function getViewerCodeService(): ViewerCodeService
    {
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        return new ViewerCodeService(
            $viewerCodeRepoProphecy->reveal(),
            $userActorLpaRepoProphecy->reveal(),
            $lpaServiceProphecy->reveal()
        );
    }

    /** @test */
    public function can_get_by_id()
    {
        $testUid = '700012349874';
        $lpaResponse = new Lpa([
            'attorneys' => [
                ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                ['id' => 2, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false],
                ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
                ['id' => 5, 'systemStatus' => true],
            ]
        ], new DateTime());
        $expectedLpaResponse = new Lpa([
            'attorneys' => [
                ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
            ],
            'original_attorneys' => [
                ['id' => 1, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                ['id' => 2, 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false],
                ['id' => 3, 'firstname' => 'A', 'systemStatus' => true],
                ['id' => 4, 'surname' => 'B', 'systemStatus' => true],
                ['id' => 5, 'systemStatus' => true],
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

        $result = $service->getByUid($testUid);

        //---

        $this->assertEquals($expectedLpaResponse, $result);
    }

    //-------------------------------------------------------------------------
    // Test getByUserLpaActorToken()

    private function init_valid_user_token_test($validState = true): stdClass
    {
        $t = new stdClass();

        $t->Token = 'test-token';
        $t->UserId = 'test-user-id';
        $t->SiriusUid = 'test-sirius-uid';
        $t->ActorId = 1;
        $t->Lpa = new Lpa(
            [
                'uId' => $t->SiriusUid,
                'status' => 'Registered',
                'attorneys' => [
                    [
                        'id' => $t->ActorId,
                        'firstname' => 'Test',
                        'surname' => 'Test',
                        'systemStatus' => true
                    ]
                ]
            ],
            new DateTime()
        );

        $this->userLpaActorMapInterfaceProphecy->get($t->Token)->willReturn([
            'Id' => $t->Token,
            'UserId' => $t->UserId,
            'SiriusUid' => $t->SiriusUid,
            'ActorId' => $t->ActorId,
        ]);

        $this->lpasInterfaceProphecy->get($t->SiriusUid)->willReturn($t->Lpa);

        // resolves LPA actor as primary attorney
        $this->resolveActorProphecy
            ->__invoke([
                'uId' => $t->SiriusUid,
                'status' => 'Registered',
                'attorneys' => [
                    [
                        'id' => $t->ActorId,
                        'firstname' => 'Test',
                        'surname' => 'Test',
                        'systemStatus' => true
                    ]
                ]
            ], (string) $t->ActorId)
            ->willReturn([
                'type' => 'primary-attorney',
                'details' => [
                    'id' => $t->ActorId,
                    'firstname' => 'Test',
                    'surname' => 'Test',
                    'systemStatus' => true
                ]
            ]);

        // check valid lpa
        $this->isValidLpaProphecy->__invoke(
            $t->Lpa->getData()
        )->willReturn($validState);

        // attorney status is active
        $this->getAttorneyStatusProphecy->__invoke([
            'id' => $t->ActorId,
            'firstname' => 'Test',
            'surname' => 'Test',
            'systemStatus' => true
        ])->willReturn(0);

        return $t;
    }

    //-------------------------------------------------------------------------
    // Test getByUserLpaActorToken()

    private function init_valid_user_token_invalid_actor(): stdClass
    {
        $t = new stdClass();

        $t->Token = 'test-token';
        $t->UserId = 'test-user-id';
        $t->SiriusUid = 'test-sirius-uid';
        $t->ActorId = 1;
        $t->Lpa = new Lpa(
            [
                'uId' => $t->SiriusUid,
                'status' => 'Registered',
                'attorneys' => [
                    [
                        'id' => $t->ActorId,
                        'firstname' => 'Test',
                        'surname' => 'Test',
                        'systemStatus' => false
                    ]
                ]
            ],
            new DateTime()
        );

        $t->LpaAfterUnsettingOriginalAttorney = new Lpa(
            [
                'uId' => $t->SiriusUid,
                'status' => 'Registered',
                'attorneys' => []
            ],
            new DateTime()
        );

        $this->userLpaActorMapInterfaceProphecy->get($t->Token)->willReturn([
            'Id' => $t->Token,
            'UserId' => $t->UserId,
            'SiriusUid' => $t->SiriusUid,
            'ActorId' => $t->ActorId,
        ]);


        $this->lpasInterfaceProphecy->get($t->SiriusUid)->willReturn($t->Lpa);

        // resolves LPA actor as primary attorney
        $this->resolveActorProphecy
            ->__invoke([
                    'uId' => $t->SiriusUid,
                    'status' => 'Registered',
                    'attorneys' => [],
                ], (string)$t->ActorId)
            ->willReturn(null);

        // check valid lpa
        $this->isValidLpaProphecy->__invoke(
            $t->LpaAfterUnsettingOriginalAttorney->getData()
        )->willReturn(true);

        // attorney status is active
        $this->getAttorneyStatusProphecy->__invoke([
             'id' => $t->ActorId,
             'firstname' => 'Test',
             'surname' => 'Test',
             'systemStatus' => false
        ])->shouldBeCalled()->willReturn(2);

        return $t;
    }

    /** @test */
    public function can_get_by_user_token()
    {
        $t = $this->init_valid_user_token_test();

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, $t->UserId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user-lpa-actor-token', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('actor', $result);
        $this->assertArrayHasKey('lpa', $result);

        $this->assertEquals($t->Token, $result['user-lpa-actor-token']);
        $this->assertEquals($t->Lpa->getLookupTime()->getTimestamp(), strtotime($result['date']));
        $this->assertEquals([
            'type' => 'primary-attorney',
            'details' => [
                'id' => $t->ActorId,
                'firstname' => 'Test',
                'surname' => 'Test',
                'systemStatus' => true
            ]
        ], $result['actor']);
        $this->assertEquals([
            'uId' => $t->SiriusUid,
            'status' => 'Registered',
            'attorneys' => [
                [
                    'id' => $t->ActorId,
                    'firstname' => 'Test',
                    'surname' => 'Test',
                    'systemStatus' => true
                ]
            ]
        ], $result['lpa']);
    }

    /** @test */
    public function cannot_get_by_user_token_with_inactive_actor()
    {
        $t = $this->init_valid_user_token_invalid_actor();

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, $t->UserId);

        $this->assertNull($result['actor']);
    }

    /** @test */
    public function cannot_get_by_user_token_with_invalid_userid()
    {
        $t = $this->init_valid_user_token_test();

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, 'different-user-id');

        $this->assertNull($result);
    }

    /** @test */
    public function cannot_get_by_user_token_with_invalid_sirius_uid()
    {
        $t = $this->init_valid_user_token_test();

        // Don't return an LPA.
        $this->lpasInterfaceProphecy->get($t->SiriusUid)->willReturn(null);

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, $t->UserId);

        $this->assertNull($result);
    }

    /** @test */
    public function cannot_get_by_user_token_when_not_valid_lpa()
    {
        $t = $this->init_valid_user_token_test(false);

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, $t->UserId);

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
                'Id' => 'token-1',
                'SiriusUid' => 'uid-1',
                'ActorId' => 1,
                'Added'   => new DateTime('now')
            ],
            [
                'Id' => 'token-2',
                'SiriusUid' => 'uid-2',
                'ActorId' => 2,
                'Added'   => new DateTime('now')
            ],
            [
                'Id' => 'token-3',
                'SiriusUid' => 'uid-3',
                'ActorId' => 3,
                'ActivateBy' => (new DateTime('now'))->add(new \DateInterval('P1Y'))->getTimeStamp(),
                'Added'   => new DateTime('now')
            ]
        ];

        $t->lpaResults = [
            'uid-1' => new Lpa([
                'uId'       => 'uid-1',
                'status'    => 'Registered',
            ], new DateTime()),
            'uid-2' => new Lpa([
                'uId'       => 'uid-2',
                'status'    => 'Registered',
            ], new DateTime())
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
            $t->lpaResults['uid-3'] = new Lpa([
                                                  'uId' => 'uid-3',
                                                  'status' => 'Registered'
                                              ], new DateTime());
            $this->lpasInterfaceProphecy->lookup(['uid-1', 'uid-2', 'uid-3'])->willReturn($t->lpaResults);

            // check valid lpa
            $this->isValidLpaProphecy->__invoke(
                $t->lpaResults['uid-3']->getData()
            )->willReturn(true);
        }

        return $t;
    }

    /** @test */
    public function can_get_all_lpas_for_user()
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

    /** @test */
    public function can_get_all_lpas_for_user_including_not_activated()
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

    /** @test */
    public function cannot_get_all_lpas_for_user_when_no_maps_found()
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
                'Id' => 'token-1',
                'SiriusUid' => 'uid-1',
                'ActorId' => 1,
                'Added'   => new DateTime('today')
            ],
            [
                'Id' => 'token-2',
                'SiriusUid' => 'uid-2',
                'ActorId' => 2,
                'Added'   => new DateTime('today')
            ]
        ];

        $lpa1 = new Lpa([
            'uId'       => 'uid-1',
            'status'    => 'Registered',
            'donor'     => [
                'linked' => [['id' => 1, 'uId' => 'person-1']]
            ],
        ], new DateTime());

        $lpa2 = new Lpa([
            'uId'       => 'uid-2',
            'status'    => 'Registered',
            'donor'     => [
                'linked' => [['id' => 2, 'uId' => 'person-2']]
            ],
        ], new DateTime());

        $t->lpaResults = [
            'uid-1' => $lpa1,
            'uid-2' => $lpa2
        ];

        $this->userLpaActorMapInterfaceProphecy->getByUserId($t->UserId)->willReturn($t->mapResults);

        $this->lpasInterfaceProphecy->lookup(array_column($t->mapResults, 'SiriusUid'))->willReturn($t->lpaResults);

        // resolves the donor as the actor for LPA 1
        $this->resolveActorProphecy->__invoke(
            $lpa1->getData(),
            '1'
        )->willReturn([
            'type' => 'donor',
            'details' => [
                'linked' => [['id' => 1, 'uId' => 'person-1']]
            ],
        ]);

        // resolves the donor as the actor for LPA 2
        $this->resolveActorProphecy->__invoke(
            $lpa2->getData(),
            '2'
        )->willReturn([
            'type' => 'donor',
            'details' => [
                'linked' => [['id' => 2, 'uId' => 'person-2']]
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

    /** @test */
    public function can_get_all_lpas_for_user_when_linked_donor()
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

        $t->ViewerCode = 'test-viewer-code';
        $t->Organisation = 'test-organisation';
        $t->DonorSurname = 'test-donor-surename';
        $t->SiriusUid = 'test-sirius-uid';
        $t->Expires = new DateTime('+1 hour');

        $t->Lpa = new Lpa([
            'uId' => $t->SiriusUid,
            'donor' => [
                'surname' => $t->DonorSurname,
            ]
        ], new DateTime());


        $this->viewerCodesInterfaceProphecy->get($t->ViewerCode)->willReturn([
            'ViewerCode' => $t->ViewerCode,
            'SiriusUid' => $t->SiriusUid,
            'Expires' => $t->Expires,
            'Organisation' => $t->Organisation,
        ]);

        $this->lpasInterfaceProphecy->get($t->SiriusUid)->willReturn($t->Lpa);

        return $t;
    }

    /** @test */
    public function can_get_lpa_by_viewer_code_no_logging()
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        //---

        // This should NOT be called when logging = false.
        $this->viewerCodeActivityInterfaceProphecy->recordSuccessfulLookupActivity(
            Argument::any()
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

    /** @test */
    public function can_get_lpa_by_viewer_code_with_logging()
    {
        $t = $this->init_valid_get_by_viewer_account();

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

    /** @test */
    public function cannot_get_lpa_by_missing_viewer_code()
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        // Change this to return null
        $this->viewerCodesInterfaceProphecy->get($t->ViewerCode)->willReturn(null)->shouldBeCalled();

        $result = $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);

        $this->assertNull($result);
    }

    /** @test */
    public function cannot_get_missing_lpa_by_viewer_code()
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        // Change this to return null
        $this->lpasInterfaceProphecy->get($t->SiriusUid)->willReturn(null)->shouldBeCalled();

        $result = $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);

        $this->assertNull($result);
    }

    /** @test */
    public function cannot_get_lpa_with_invalid_donor_by_viewer_code()
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        $result = $service->getByViewerCode($t->ViewerCode, 'different-donor-name', null);

        $this->assertNull($result);
    }

    /** @test */
    public function cannot_get_lpa_by_viewer_code_with_missing_expiry()
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        //---

        $this->viewerCodesInterfaceProphecy->get($t->ViewerCode)->willReturn([
            'ViewerCode' => $t->ViewerCode,
            'SiriusUid' => $t->SiriusUid,
            //'Expires' => $t->Expires,             <-- Expires is removed
            'Organisation' => $t->Organisation,
        ]);

        //---

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("'Expires' field missing or invalid.");

        $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);
    }

    /** @test */
    public function cannot_get_lpa_by_viewer_code_with_cancelled()
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        //---

        $this->viewerCodesInterfaceProphecy->get($t->ViewerCode)->willReturn([
            'ViewerCode' => $t->ViewerCode,
            'SiriusUid' => $t->SiriusUid,
            'Expires' => new DateTime('1 hour'),
            'Cancelled' => true,
            'Organisation' => $t->Organisation,
        ]);

        //---

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Share code cancelled");

        $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);
    }

    /** @test */
    public function cannot_get_lpa_by_viewer_code_with_expired_expiry()
    {
        $t = $this->init_valid_get_by_viewer_account();

        $service = $this->getLpaService();

        //---

        $this->viewerCodesInterfaceProphecy->get($t->ViewerCode)->willReturn([
            'ViewerCode' => $t->ViewerCode,
            'SiriusUid' => $t->SiriusUid,
            'Expires' => new DateTime('-1 hour'),
            'Organisation' => $t->Organisation,
        ]);

        //---

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Share code expired");

        $service->getByViewerCode($t->ViewerCode, $t->DonorSurname, null);
    }

    /** @test */
    public function will_return_empty_lpa_array_when_status_invalid()
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
