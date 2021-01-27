<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\{ApiGateway\ActorCodes,
    Repository,
    Repository\UserLpaActorMapInterface,
    Repository\ViewerCodesInterface};
use App\DataAccess\Repository\Response\{ActorCode, Lpa};
use App\Exception\ApiException;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Lpa\LpaService;
use App\Service\ViewerCodes\ViewerCodeService;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
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

    public function setUp()
    {
        $this->viewerCodesInterfaceProphecy = $this->prophesize(Repository\ViewerCodesInterface::class);
        $this->viewerCodeActivityInterfaceProphecy = $this->prophesize(Repository\ViewerCodeActivityInterface::class);
        $this->lpasInterfaceProphecy = $this->prophesize(Repository\LpasInterface::class);
        $this->userLpaActorMapInterfaceProphecy = $this->prophesize(Repository\UserLpaActorMapInterface::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->actorCodesProphecy = $this->prophesize(ActorCodes::class);
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
            $this->actorCodesProphecy->reveal()
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

        $result = $service->getByUid($testUid);

        //---

        $this->assertEquals($expectedLpaResponse, $result);
    }

    //-------------------------------------------------------------------------
    // Test getByUserLpaActorToken()

    private function init_valid_user_token_test(): stdClass
    {
        $t = new stdClass();

        $t->Token = 'test-token';
        $t->UserId = 'test-user-id';
        $t->SiriusUid = 'test-sirius-uid';
        $t->ActorId = 1;
        $t->Lpa = new Lpa(
            [
                'uId' => $t->SiriusUid,
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
            'UserId' => $t->SiriusUid,
            'SiriusUid' => $t->SiriusUid,
            'ActorId' => $t->ActorId,
        ]);

        $this->lpasInterfaceProphecy->get($t->SiriusUid)->willReturn($t->Lpa);

        return $t;
    }

    /** @test */
    public function can_get_by_user_token()
    {
        $t = $this->init_valid_user_token_test();

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, $t->SiriusUid);

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
        $t = $this->init_valid_user_token_test();

        $this->lpasInterfaceProphecy->get($t->SiriusUid)->willReturn([
            'uId' => $t->SiriusUid,
            'attorneys' => [
                [
                    'id' => $t->ActorId,
                    'firstname' => 'Test',
                    'surname' => 'Test',
                    'systemStatus' => false
                ]
            ]
        ]);

        $service = $this->getLpaService();

        $result = $service->getByUserLpaActorToken($t->Token, 'different-user-id');

        $this->assertNull($result);
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

        $result = $service->getByUserLpaActorToken($t->Token, $t->SiriusUid);

        $this->assertNull($result);
    }

    //-------------------------------------------------------------------------
    // Test getAllForUser()

    private function init_valid_get_all_users()
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
            ]
        ];

        $t->lpaResults = [
            'uid-1' => new Lpa([
                'uId' => 'uid-1'
            ], new DateTime()),
            'uid-2' => new Lpa([
                'uId' => 'uid-2'
            ], new DateTime()),
        ];

        $this->userLpaActorMapInterfaceProphecy->getUsersLpas($t->UserId)->willReturn($t->mapResults);

        $this->lpasInterfaceProphecy->lookup(array_column($t->mapResults, 'SiriusUid'))->willReturn($t->lpaResults);

        return $t;
    }

    /** @test */
    public function can_get_all_lpas_for_user()
    {
        $t = $this->init_valid_get_all_users();

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
        $this->assertEquals(null, $result['actor']);    // We're expecting no actor to have been found
        $this->assertEquals($lpa->getData(), $result['lpa']);
    }

    /** @test */
    public function cannot_get_all_lpas_for_user_when_no_maps_found()
    {
        $t = $this->init_valid_get_all_users();

        $service = $this->getLpaService();

        $this->userLpaActorMapInterfaceProphecy->getUsersLpas($t->UserId)->willReturn([]);

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

        $t->lpaResults = [
            'uid-1' => new Lpa([
                'uId' => 'uid-1',
                'donor' => [
                    'linked' => [['id' => 1, 'uId' => 'person-1']]
                ],
            ], new DateTime()),
            'uid-2' => new Lpa([
                'uId' => 'uid-2',
                'donor' => [
                    'linked' => [['id' => 2, 'uId' => 'person-2']]
                ],
            ], new DateTime()),
        ];

        $this->userLpaActorMapInterfaceProphecy->getUsersLpas($t->UserId)->willReturn($t->mapResults);

        $this->lpasInterfaceProphecy->lookup(array_column($t->mapResults, 'SiriusUid'))->willReturn($t->lpaResults);

        return $t;
    }

    /** @test */
    public function can_get_all_lpas_for_user_when_linked_donor()
    {
        $t = $this->init_valid_get_all_users_with_linked();

        $this->userLpaActorMapInterfaceProphecy->getUsersLpas($t->UserId)->willReturn($t->mapResults);

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

    //-------------------------------------------------------------------------
    // Test lookupActorInLpa()

    /** @test */
    public function can_find_actor_who_is_a_donor()
    {
        $lpa = [
            'donor' => [
                'id'  => 1,
                'uId' => '123456789012'
            ]
        ];

        $service = $this->getLpaService();

        $result = $service->lookupActiveActorInLpa($lpa, '1');

        $this->assertEquals(
            [
                'type' => 'donor',
                'details' => $lpa['donor'],
            ],
            $result
        );

        $result = $service->lookupActiveActorInLpa($lpa, '123456789012');

        $this->assertEquals(
            [
                'type' => 'donor',
                'details' => $lpa['donor'],
            ],
            $result
        );
    }

    /** @test */
    public function can_find_actor_who_is_a_donor_by_linked_id()
    {
        $lpa = [
            'donor' => [
                'id'  => 1,
                'uId' => '123456789013',
                'linked' => [['id' => 1, 'uId' => '123456789013'], ['id' => 2, 'uId' => '123456789012']],
            ]
        ];

        $service = $this->getLpaService();

        $result = $service->lookupActiveActorInLpa($lpa, '2');

        $this->assertEquals(
            [
                'type' => 'donor',
                'details' => $lpa['donor'],
            ],
            $result
        );
    }

    /** @test */
    public function can_find_actor_who_is_a_donor_by_linked_uid()
    {
        $lpa = [
            'donor' => [
                'id'  => 1,
                'uId' => '123456789013',
                'linked' => [['id' => 1, 'uId' => '123456789013'], ['id' => 2, 'uId' => '123456789012']],
            ]
        ];

        $service = $this->getLpaService();

        $result = $service->lookupActiveActorInLpa($lpa, '123456789012');

        $this->assertEquals(
            [
                'type' => 'donor',
                'details' => $lpa['donor'],
            ],
            $result
        );
    }

    /** @test */
    public function can_not_find_actor_who_is_not_a_donor_by_linked_id()
    {
        $lpa = [
            'donor' => [
                'id'  => 1,
                'uId' => '123456789013',
                'linked' => [['id' => 1, 'uId' => '123456789013'], ['id' => 2, 'uId' => '123456789012']],
            ]
        ];

        $service = $this->getLpaService();

        $result = $service->lookupActiveActorInLpa($lpa, '3');

        $this->assertNull($result);
    }

    /** @test */
    public function can_not_find_actor_who_is_not_a_donor_by_linked_uid()
    {
        $lpa = [
            'donor' => [
                'id'  => 1,
                'uId' => '123456789013',
                'linked' => [['id' => 1, 'uId' => '123456789013'], ['id' => 2, 'uId' => '123456789012']],
            ]
        ];

        $service = $this->getLpaService();

        $result = $service->lookupActiveActorInLpa($lpa, '123456789999');

        $this->assertNull($result);
    }

    /** @test */
    public function can_find_actor_who_is_an_attorney()
    {
        $lpa = [
            'donor' => [
                'id' => 1,
            ],
            'original_attorneys' => [
                ['id' => 1, 'uId' => '123456789012', 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                ['id' => 3, 'uId' => '234567890123', 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                ['id' => 7, 'uId' => '345678901234', 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true]
            ],
        ];

        $service = $this->getLpaService();

        $result = $service->lookupActiveActorInLpa($lpa, '3');

        $this->assertEquals(
            [
                'type' => 'primary-attorney',
                'details' => [
                    'id' => 3,
                    'uId' => '234567890123',
                    'firstname' => 'A',
                    'surname' => 'B',
                    'systemStatus' => true
                ],
            ],
            $result
        );

        $result = $service->lookupActiveActorInLpa($lpa, '234567890123');

        $this->assertEquals(
            [
                'type' => 'primary-attorney',
                'details' => [
                    'id' => 3,
                    'uId' => '234567890123',
                    'firstname' => 'A',
                    'surname' => 'B',
                    'systemStatus' => true
                ],
            ],
            $result
        );
    }

    /** @test */
    public function can_not_find_actor_who_is_a_ghost_attorney()
    {
        $lpa = [
            'donor' => [
                'id' => 1,
            ],
            'original_attorneys' => [
                ['id' => 2, 'uId' => '123456789012', 'systemStatus' => true],
                ['id' => 3, 'uId' => '234567890123', 'firstname' => 'A', 'systemStatus' => true],
                ['id' => 7, 'uId' => '345678901234', 'surname' => 'B', 'systemStatus' => true]
            ],
        ];

        $service = $this->getLpaService();

        $result = $service->lookupActiveActorInLpa($lpa, '2');
        $this->assertNull($result);

        $result = $service->lookupActiveActorInLpa($lpa, '123456789012');
        $this->assertNull($result);

        $result = $service->lookupActiveActorInLpa($lpa, '3');
        $this->assertNotNull($result);

        $result = $service->lookupActiveActorInLpa($lpa, '234567890123');
        $this->assertNotNull($result);

        $result = $service->lookupActiveActorInLpa($lpa, '7');
        $this->assertNotNull($result);

        $result = $service->lookupActiveActorInLpa($lpa, '345678901234');
        $this->assertNotNull($result);
    }

    /** @test */
    public function can_not_find_actor_who_is_an_inactive_attorney()
    {
        $lpa = [
            'donor' => [
                'id' => 1,
            ],
            'original_attorneys' => [
                ['id' => 1, 'uId' => '123456789012', 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                ['id' => 3, 'uId' => '234567890123', 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false],
                ['id' => 7, 'uId' => '345678901234', 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true]
            ],
        ];

        $service = $this->getLpaService();

        $result = $service->lookupActiveActorInLpa($lpa, '3');
        $this->assertNull($result);

        $result = $service->lookupActiveActorInLpa($lpa, '234567890123');
        $this->assertNull($result);
    }

    /** @test */
    public function remove_lpa_from_user_lpa_actor_map_successfully()
    {
        $userActorLpa = [
                'SiriusUid' => '700000055554',
                'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id' => '2345Token0123',
                'ActorId' => '1',
                'UserId' => '1234',
            ];

        $viewerCodes = [
            'Id'            => '1',
            'ViewerCode'    => '123ABCD6789',
            'SiriusUid'     => '700000055554',
            'Added'         => '2020-01-01 00:00:00',
            'Expires'       => '2021-02-01 00:00:00',
            'UserLpaActor' => '2345Token0123',
            'Organisation' => 'Some Organisation',
        ];

        $removedresponse = [
            'Id' => '1',
            'SiriusUid' => '700000055554',
            'Added' => '2020-01-01 00:00:00',
            'ActorId' => '1',
            'UserId' => '1234',
        ];

        $this->userLpaActorMapInterfaceProphecy
            ->get('2345Token0123')
            ->willReturn($userActorLpa)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy->getCodesByLpaId($userActorLpa['SiriusUid'])->willReturn($viewerCodes);
        $this->viewerCodesInterfaceProphecy->removeActorAssociation($viewerCodes['ViewerCode'])->willReturn(true);
        $this->userLpaActorMapInterfaceProphecy->delete('2345Token0123')->willReturn($removedresponse);

        $service = $this->getLpaService();
        $result = $service->removeLpaFromUserLpaActorMap('1234', '2345Token0123');

        $this->assertNotEmpty($result);
        $this->assertEquals($result['SiriusUid'], $userActorLpa['SiriusUid']);
    }

    /** @test */
    public function remove_lpa_from_user_lpa_actor_map_when_no_viewer_codes_to_update()
    {
        $userActorLpa = [
            'SiriusUid' => '700000055554',
            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
            'Id' => '2345Token0123',
            'ActorId' => '1',
            'UserId' => '1234',
        ];

        $viewerCodes = [];

        $removedresponse = [
            'Id' => '1',
            'SiriusUid' => '700000055554',
            'Added' => '2020-01-01 00:00:00',
            'ActorId' => '1',
            'UserId' => '1234',
        ];

        $this->userLpaActorMapInterfaceProphecy
            ->get('2345Token0123')
            ->willReturn($userActorLpa)
            ->shouldBeCalled();

        $this->viewerCodesInterfaceProphecy->getCodesByLpaId($userActorLpa['SiriusUid'])->willReturn($viewerCodes);
        $this->viewerCodesInterfaceProphecy->removeActorAssociation($viewerCodes['ViewerCode'])->willReturn(true);
        $this->userLpaActorMapInterfaceProphecy->delete('2345Token0123')->willReturn($removedresponse);

        $service = $this->getLpaService();
        $result = $service->removeLpaFromUserLpaActorMap('1234', '2345Token0123');

        $this->assertNotEmpty($result);
        $this->assertEquals($result['SiriusUid'], $userActorLpa['SiriusUid']);
    }

    /** @test */
    public function remove_lpa_from_user_lpa_actor_map_when_actor_token_not_found()
    {
        $userActorLpa = null;
        $viewerCodes = null;

        $this->userLpaActorMapInterfaceProphecy
            ->get('2345Token0123')
            ->willReturn($userActorLpa)
            ->shouldBeCalled();

        $this->expectException(NotFoundException::class);

        $service = $this->getLpaService();
        $result = $service->removeLpaFromUserLpaActorMap('1234', '2345Token0123');
    }

    /** @test */
    public function remove_lpa_from_user_lpa_actor_map_when_user_id_does_not_match()
    {
        $userActorLpa = [
            'SiriusUid' => '700000055554',
            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
            'Id' => '2345Token0123',
            'ActorId' => '1',
            'UserId' => '6789',
        ];

        $this->userLpaActorMapInterfaceProphecy
            ->get('2345Token0123')
            ->willReturn($userActorLpa)
            ->shouldBeCalled();


        $this->expectException(NotFoundException::class);

        $service = $this->getLpaService();
        $result = $service->removeLpaFromUserLpaActorMap('1234', '2345Token0123');
    }

    /** @test */
    public function request_access_code_letter(): void
    {
        $caseUid = '700000055554';
        $actorUid = '700000055554';

        $this->lpasInterfaceProphecy
            ->requestLetter((int) $caseUid, (int)$actorUid)
            ->shouldBeCalled();

        $service = $this->getLpaService();
        $service->requestAccessByLetter($caseUid, $actorUid);
    }

    /** @test */
    public function request_access_code_letter_api_call_fails(): void
    {
        $caseUid = '700000055554';
        $actorUid = '700000055554';

        $this->lpasInterfaceProphecy
            ->requestLetter((int) $caseUid, (int)$actorUid)
            ->willThrow(ApiException::create('bad api call'));

        $service = $this->getLpaService();

        $this->expectException(ApiException::class);
        $service->requestAccessByLetter($caseUid, $actorUid);
    }

    /** @test */
    public function returns_true_if_a_code_exists_for_an_actor()
    {
        $actorUid = '700000055554';
        $lpaId = '700000012345';

        $lpaCodesResponse = new ActorCode(
            [
                'Created' => '2021-01-01'
            ],
            new DateTime('now')
        );

        $this->actorCodesProphecy
            ->checkActorHasCode($lpaId, $actorUid)
            ->willReturn($lpaCodesResponse);

        $service = $this->getLpaService();

        $codeExists = $service->hasActivationCode($lpaId, $actorUid);
        $this->assertTrue($codeExists);
    }

    /** @test */
    public function returns_false_if_a_code_does_not_exist_for_an_actor()
    {
        $actorUid = '700000055554';
        $lpaId = '700000012345';

        $lpaCodesResponse = new ActorCode(
            [
                'Created' => null
            ],
            new DateTime()
        );

        $this->actorCodesProphecy
            ->checkActorHasCode($lpaId, $actorUid)
            ->willReturn($lpaCodesResponse);

        $service = $this->getLpaService();

        $codeExists = $service->hasActivationCode($lpaId, $actorUid);
        $this->assertFalse($codeExists);
    }

    /**
     * @test
     * @dataProvider registeredDataProvider
     * @param array $lpa
     * @param bool $isValid
     * @throws \Exception
     */
    public function checks_whether_the_lpa_was_registered_after_1st_Sept_2019(array $lpa, bool $isValid)
    {
        $service = $this->getLpaService();

        $registrationValid = $service->checkLpaRegistrationDetails($lpa);
        $this->assertEquals($isValid, $registrationValid);
    }

    public function registeredDataProvider(): array
    {
        return [
            [
                [
                    'status' => 'Registered',
                    'registrationDate' => '2021-01-01'
                ],
                true
            ],
            [
                [
                    'status' => 'Cancelled',
                    'registrationDate' => '2021-01-01'
                ],
                false
            ],
            [
                [
                    'status' => 'Registered',
                    'registrationDate' => '2019-09-01'
                ],
                true
            ],
            [
                [
                    'status' => 'Registered',
                    'registrationDate' => '2019-08-31'
                ],
                false
            ]
        ];
    }

    /** @test */
    public function returns_data_in_correct_format_after_cleansing()
    {
        $data = [
          'dob'         => '01/03/1980',
          'first_names' => 'Test Tester',
          'last_name'   => 'Testing',
          'postcode'    => 'Ab1 2Cd'
        ];

        $service = $this->getLpaService();

        $cleansedData = $service->cleanseUserData($data);
        $this->assertInstanceOf(DateTime::class, $cleansedData['dob']);
        $this->assertEquals(new DateTime('1980-03-01'), $cleansedData['dob']);
        $this->assertEquals('test', $cleansedData['first_names']);
        $this->assertEquals('testing', $cleansedData['last_name']);
        $this->assertEquals('ab12cd', $cleansedData['postcode']);
    }

    /** @test */
    public function returns_the_actor_if_user_data_matches_the_actor_data()
    {
        $actor = [
            'dob'       => '1980-03-01',
            'firstname' => 'Test',
            'surname'   => 'Testing',
            'addresses' => [
                ['postcode' => 'Ab1 2Cd']
            ]
        ];

        $userData = [
            'dob'         => '01/03/1980',
            'first_names' => 'Test Tester',
            'last_name'   => 'Testing',
            'postcode'    => 'Ab1 2Cd'
        ];

        $service = $this->getLpaService();

        $userData = $service->cleanseUserData($userData);

        $actorMatch = $service->checkDataMatch($actor, $userData);
        $this->assertEquals($actor, $actorMatch);
    }

    /** @test */
    public function returns_null_if_actor_has_more_than_one_address()
    {
        $actor = [
          'addresses' => [
              ['postcode' => 'ab1 2cd'],
              ['postcode' => 'gw1 9hp']
          ]
        ];

        $service = $this->getLpaService();

        $dataMatch = $service->checkDataMatch($actor, []);
        $this->assertNull($dataMatch);
    }

    /**
     * @test
     * @dataProvider actorLookupDataProvider
     * @param array|null $expectedResponse
     * @param array $userData
     */
    public function returns_actor_and_lpa_id_if_match_found_in_lookup(?array $expectedResponse, array $userData)
    {
        $lpaId = '700000009999';

        $lpa = [
            'uId' => $lpaId,
            'donor' => [
                'uId' => '700000001111',
                'dob' => '1975-10-05',
                'firstname' => 'Donor',
                'surname'   => 'Person',
                'addresses' => [
                    [
                        'postcode' => 'PY1 3Kd'
                    ]
                ]
            ],
            'attorneys' => [
                [
                    'uId' => '700000002222',
                    'dob' => '1977-11-21',
                    'firstname' => 'Attorneyone',
                    'surname'   => 'Person',
                    'addresses' => [
                        [
                            'postcode' => 'Gg1 2ff'
                        ]
                    ],
                    'systemStatus' => false,
                ],
                [
                    'uId' => '700000003333',
                    'dob' => '1960-05-05',
                    'firstname' => '', // ghost attorney
                    'surname'   => '',
                    'addresses' => [
                        [
                            'postcode' => 'BB1 9ee'
                        ]
                    ],
                    'systemStatus' => true,
                ],
                [
                    'uId' => '700000001234',
                    'dob' => '1980-03-01',
                    'firstname' => 'Test',
                    'surname'   => 'Testing',
                    'addresses' => [
                        [
                            'postcode' => 'Ab1 2Cd'
                        ]
                    ],
                    'systemStatus' => true,
                ]
            ]
        ];

        $service = $this->getLpaService();

        $userData = $service->cleanseUserData($userData);

        $actorMatch = $service->compareAndLookupActiveActorInLpa($lpa, $userData);
        $this->assertEquals($expectedResponse, $actorMatch);
    }

    public function actorLookupDataProvider(): array
    {
        return [
            [
                [
                    'actor-id' => '700000001234', // successful match for attorney
                    'lpa-id'   => '700000009999'
                ],
                [
                    'dob'         => '01/03/1980',
                    'first_names' => 'Test Tester',
                    'last_name'   => 'Testing',
                    'postcode'    => 'Ab1 2Cd'
                ],
            ],
            [
                [
                    'actor-id' => '700000001111', // successful match for donor
                    'lpa-id'   => '700000009999'
                ],
                [
                    'dob'         => '05/10/1975',
                    'first_names' => 'Donor',
                    'last_name'   => 'Person',
                    'postcode'    => 'PY1 3Kd'
                ],
            ],
            [
                null,
                [
                    'dob'         => '20/01/1980', // dob will not match
                    'first_names' => 'Test Tester',
                    'last_name'   => 'Testing',
                    'postcode'    => 'Ab1 2Cd'
                ],
            ],
            [
                null,
                [
                    'dob'         => '01/03/1980',
                    'first_names' => 'Wrong', // firstname will not match
                    'last_name'   => 'Testing',
                    'postcode'    => 'Ab1 2Cd'
                ],
            ],
            [
                null,
                [
                    'dob'         => '01/03/1980',
                    'first_names' => 'Test Tester',
                    'last_name'   => 'Incorrect', // surname will not match
                    'postcode'    => 'Ab1 2Cd'
                ],
            ],
            [
                null,
                [
                    'dob'         => '01/03/1980',
                    'first_names' => 'Test Tester',
                    'last_name'   => 'Testing',
                    'postcode'    => 'WR0 NG1' // postcode will not match
                ],
            ],
            [
                null, // will not find a match as this attorney is inactive
                [
                    'dob'         => '21/11/1977',
                    'first_names' => 'Attorneyone',
                    'last_name'   => 'Person',
                    'postcode'    => 'Gg1 2ff'
                ],
            ],
            [
                null, // will not find a match as this attorney is a ghost
                [
                    'dob'         => '05/05/1960',
                    'first_names' => 'Attorneytwo',
                    'last_name'   => 'Person',
                    'postcode'    => 'BB1 9ee'
                ],
            ]
        ];
    }

    /**
     * @test
     * @throws \Exception
     */
    public function older_lpa_lookup_throws_an_exception_if_lpa_not_found()
    {
        $lpaId = '700000004321';

        $dataToMatch = [
            'reference_number' => $lpaId,
            'dob'              => '01/03/1980',
            'first_names'      => 'Test Tester',
            'last_name'        => 'Testing',
            'postcode'         => 'Ab1 2Cd'
        ];

        $service = $this->getLpaService();

        $this->lpasInterfaceProphecy
            ->get($lpaId)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->expectExceptionMessage('LPA not found');

        $service->checkLPAMatchAndGetActorDetails($dataToMatch);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function older_lpa_lookup_throws_an_exception_if_lpa_registration_not_valid()
    {
        $lpaId = '700000004321';

        $dataToMatch = [
            'reference_number' => $lpaId,
            'dob'              => '01/03/1980',
            'first_names'      => 'Test Tester',
            'last_name'        => 'Testing',
            'postcode'         => 'Ab1 2Cd'
        ];

        $service = $this->getLpaService();

        $this->lpasInterfaceProphecy
            ->get($lpaId)
            ->willReturn(
                new Lpa(
                    [
                        'uId' => $lpaId,
                        'registrationDate' => '2019-08-31',
                        'status' => 'Registered',
                    ],
                    new DateTime()
                )
            );

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectExceptionMessage('LPA not eligible due to registration date');

        $service->checkLPAMatchAndGetActorDetails($dataToMatch);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function older_lpa_lookup_throws_an_exception_if_user_data_doesnt_match_lpa()
    {
        $lpaId = '700000004321';

        $dataToMatch = [
            'reference_number' => $lpaId,
            'dob'              => '08/08/1970',
            'first_names'      => 'Wrong Name',
            'last_name'        => 'Incorrect',
            'postcode'         => 'wR0 nG1'
        ];

        $service = $this->getLpaService();

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpasInterfaceProphecy
            ->get($lpaId)
            ->willReturn($lpa);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectExceptionMessage('LPA details does not match');

        $service->checkLPAMatchAndGetActorDetails($dataToMatch);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function older_lpa_lookup_throws_an_exception_if_actor_has_activation_key()
    {
        $lpaId = '700000004321';
        $actorId = '700000004444';

        $dataToMatch = [
            'reference_number' => $lpaId,
            'dob'              => '01/03/1980',
            'first_names'      => 'Test Tester',
            'last_name'        => 'Testing',
            'postcode'         => 'Ab1 2Cd'
        ];

        $service = $this->getLpaService();

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpasInterfaceProphecy
            ->get($lpaId)
            ->willReturn($lpa);

        $this->actorCodesProphecy
            ->checkActorHasCode($lpaId, $actorId)
            ->willReturn(new ActorCode(
                [
                    'Created' => (new DateTime('-1 week'))->format('Y-m-d')
                ],
                new DateTime()
            ));

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->expectExceptionMessage('LPA not eligible as an activation key already exists');

        $service->checkLPAMatchAndGetActorDetails($dataToMatch);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function returns_matched_actorId_and_lpaId_when_passing_all_older_lpa_criteria()
    {
        $lpaId = '700000004321';
        $actorId = '700000004444';

        $dataToMatch = [
            'reference_number' => $lpaId,
            'dob'              => '01/03/1980',
            'first_names'      => 'Test Tester',
            'last_name'        => 'Testing',
            'postcode'         => 'Ab1 2Cd'
        ];

        $service = $this->getLpaService();

        $lpa = $this->older_lpa_get_by_uid_response();

        $this->lpasInterfaceProphecy
            ->get($lpaId)
            ->willReturn($lpa);

        $this->actorCodesProphecy
            ->checkActorHasCode($lpaId, $actorId)
            ->willReturn(new ActorCode(
                [
                    'Created' => null
                ],
                new DateTime()
            ));

        $response = $service->checkLPAMatchAndGetActorDetails($dataToMatch);

        $this->assertEquals($actorId, $response['actor-id']);
        $this->assertEquals($lpaId, $response['lpa-id']);
    }

    /**
     * Returns the lpa data needed for checking in the older LPA journey
     *
     * @return Lpa
     */
    public function older_lpa_get_by_uid_response(): Lpa
    {
        return new Lpa(
            [
                'uId' => '700000004321',
                'registrationDate' => '2021-01-01',
                'status' => 'Registered',
                'donor' => [
                    'uId' => '700000001111',
                    'dob' => '1975-10-05',
                    'firstname' => 'Donor',
                    'surname'   => 'Person',
                    'addresses' => [
                        [
                            'postcode' => 'PY1 3Kd'
                        ]
                    ]
                ],
                'attorneys' => [
                    [
                        'uId' => '700000002222',
                        'dob' => '1977-11-21',
                        'firstname' => 'Attorneyone',
                        'surname'   => 'Person',
                        'addresses' => [
                            [
                                'postcode' => 'Gg1 2ff'
                            ]
                        ],
                        'systemStatus' => false,
                    ],
                    [
                        'uId' => '700000004444',
                        'dob' => '1980-03-01',
                        'firstname' => 'Test',
                        'surname'   => 'Testing',
                        'addresses' => [
                            [
                                'postcode' => 'Ab1 2Cd'
                            ]
                        ],
                        'systemStatus' => true,
                    ]
                ]
            ],
            new DateTime()
        );
    }
}
