<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository;
use App\DataAccess\Repository\Response\Lpa;
use App\Service\Lpa\LpaService;
use App\Service\ViewerCodes\ViewerCodeService;
use DateTime;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use RuntimeException;
use App\Exception\NotFoundException;

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
     * @var Repository\ViewerCodesInterface
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
     * @var string
     */
    private $organisation;

    public function setUp()
    {
        $this->viewerCodesInterfaceProphecy = $this->prophesize(Repository\ViewerCodesInterface::class);
        $this->viewerCodeActivityInterfaceProphecy = $this->prophesize(Repository\ViewerCodeActivityInterface::class);
        $this->lpasInterfaceProphecy = $this->prophesize(Repository\LpasInterface::class);
        $this->userLpaActorMapInterfaceProphecy = $this->prophesize(Repository\UserLpaActorMapInterface::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
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
            $this->loggerProphecy->reveal()
        );
    }

    private function getViewerCodeService(): ViewerCodeService
    {
        $viewerCodeRepoProphecy = $this->prophesize(ViewerCodesInterface::class);
        $userActorLpaRepoProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $lpaServiceProphecy = $this->prophesize(LpaService::class);

        $service = new ViewerCodeService(
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

    private function init_valid_user_token_test()
    {
        $t = new \StdClass();

        $t->Token = 'test-token';
        $t->UserId = 'test-user-id';
        $t->SiriusUid = 'test-sirius-uid';
        $t->ActorId = 1;
        $t->Lpa = new Lpa([
            'uId' => $t->SiriusUid,
            'attorneys' => [
                [
                    'id' => $t->ActorId,
                    'firstname' => 'Test',
                    'surname' => 'Test',
                    'systemStatus' => true
                ]
            ]
        ], new DateTime);

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
        $t = new \StdClass();

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
            ], new DateTime),
            'uid-2' => new Lpa([
                'uId' => 'uid-2'
            ], new DateTime),
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
        $this->assertEquals(null, $result['actor']);    // We expexting not actor to have been found
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
        $t = new \StdClass();

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
            ], new DateTime),
            'uid-2' => new Lpa([
                'uId' => 'uid-2',
                'donor' => [
                    'linked' => [['id' => 2, 'uId' => 'person-2']]
                ],
            ], new DateTime),
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
        $t = new \StdClass();

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
        ], new DateTime);


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
        $this->viewerCodeActivityInterfaceProphecy->recordSuccessfulLookupActivity(Argument::any())->shouldNotBeCalled();

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
        $this->viewerCodeActivityInterfaceProphecy->recordSuccessfulLookupActivity($t->ViewerCode, $t->Organisation)->shouldBeCalled();

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
    public function remove_lpa_from_user_lpa_actor_map_invalid_token()
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
        $result = $service->removeLPaFromUserLpaActorMap('2345Token0123');
        $this->assertEquals($result['SiriusUid'], $userActorLpa['SiriusUid']);
    }
}
