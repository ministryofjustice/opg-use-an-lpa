<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Service\ApiClient\Client;
use Common\Service\Lpa\ViewerCodeService;
use PHPUnit\Framework\TestCase;
use DateTime;

class ViewerCodeServiceTest extends TestCase
{
    const IDENTITY_TOKEN = '01234567-01234-01234-01234-012345678901';
    const SORT_ADDED = 'Added';
    //const IDENTITY_TOKEN = '01234567-01234-01234-01234-012345678901';
    const LPA_ID = '98765432-12345-54321-12345-9876543210';
    const ACTOR_ID = 10;
    const FIRST_NAME = "John";
    const SUR_NAME = "Will";
    const CSRF_CODE = '1234';

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|Client
     */
    private $apiClientProphecy;

    public function setUp()
    {
        $this->apiClientProphecy = $this->prophesize(Client::class);

        // all methods in this
        $this->apiClientProphecy
            ->setUserTokenHeader(self::IDENTITY_TOKEN)
            ->shouldBeCalled();
    }

    /** @test */
    public function it_creates_a_new_viewercode_given_correct_details()
    {
        $lpaId      = '700000000047';
        $viewerCode = '123456789012';
        $expiry     = (new \DateTime('now +30 days'))->format('c');
        $orgName    = 'Test Org';

        $return = [
            'code'         => $viewerCode,
            'expiry'       => $expiry,
            'organisation' => $orgName
        ];

        $this->apiClientProphecy
            ->httpPost('/v1/lpas/' . $lpaId . '/codes', ['organisation' => $orgName])
            ->willReturn($return);

        $viewerCodeService = new ViewerCodeService($this->apiClientProphecy->reveal());

        $codeData = $viewerCodeService->createShareCode(self::IDENTITY_TOKEN, $lpaId, $orgName);

        $this->assertInstanceOf(ArrayObject::class, $codeData);
        $this->assertEquals($viewerCode, $codeData->code);
        $this->assertInstanceOf(\DateTime::class, new \DateTime($codeData->expiry));
        $this->assertEquals($orgName, $codeData->organisation);
    }

    /** @test */
    public function it_gets_share_codes_for_a_given_lpa()
    {
        $lpaId = '98765432-01234-01234-01234-012345678901';

        $return = [
            [
                'UserLpaActor' => $lpaId
            ]
        ];

        $this->apiClientProphecy
            ->httpGet('/v1/lpas/' . $lpaId . '/codes')
            ->willReturn($return);

        $viewerCodeService = new ViewerCodeService($this->apiClientProphecy->reveal());

        $shareCodes = $viewerCodeService->getShareCodes(self::IDENTITY_TOKEN, $lpaId, false);

        $this->assertInstanceOf(ArrayObject::class, $shareCodes);
        $this->assertEquals($lpaId, $shareCodes[0]['UserLpaActor']);
    }

    /** @test */
    public function returns_empty_array_if_no_share_codes_generated()
    {
        $lpaId = '98765432-01234-01234-01234-012345678902';

        $this->apiClientProphecy
            ->httpGet('/v1/lpas/' . $lpaId . '/codes')
            ->willReturn([]);

        $viewerCodeService = new ViewerCodeService($this->apiClientProphecy->reveal());

        $shareCodes = $viewerCodeService->getShareCodes(self::IDENTITY_TOKEN, $lpaId, false);

        $this->assertInstanceOf(ArrayObject::class, $shareCodes);
        $this->assertEmpty($shareCodes);
    }

    /** @test */
    public function gets_number_of_active_codes_for_lpa()
    {
        $lpaId = '98765432-01234-01234-01234-012345678902';

        $endOfToday =  (new DateTime('now'))->setTime(23,59,59)->format('c');
        $currentDateTime =  (new DateTime('now'))->setTime(16,59,59)->format('c');
        $futureWeek = (new DateTime('+1 week'))->format('c');
        $pastWeek = (new DateTime('-1 week'))->format('c');

        $return = [
            [
                'Added' => '2020-09-16 22:00:00',
                'UserLpaActor' => $lpaId,
                'Expires' => $pastWeek,
            ],
            [
                'Added' => '2020-09-16 22:00:00',
                'UserLpaActor' => $lpaId,
                'Expires' => $futureWeek,
            ],
            [
                'Added' => '2020-09-16 22:00:00',
                'UserLpaActor' => $lpaId,
                'Expires' => $endOfToday,
            ],
        ];

        $this->apiClientProphecy
            ->httpGet('/v1/lpas/' . $lpaId . '/codes')
            ->willReturn($return);

        $viewerCodeService = new ViewerCodeService($this->apiClientProphecy->reveal());

        $shareCodes = $viewerCodeService->getShareCodes(self::IDENTITY_TOKEN, $lpaId, true);

        $this->assertInstanceOf(ArrayObject::class, $shareCodes);
        $this->assertEquals($lpaId, $shareCodes[0]['UserLpaActor']);
        $this->assertLessThan($currentDateTime, $shareCodes[0]['Expires']);
        $this->assertGreaterThan($currentDateTime, $shareCodes[1]['Expires']);
        $this->assertGreaterThan($currentDateTime, $shareCodes[2]['Expires']);
        $this->assertEquals(2, $shareCodes['activeCodeCount']);
    }

    /** @test */
    public function it_cancels_a_new_viewercode_given_correct_details()
    {
        $lpaId      = '700000000047';
        $viewerCode = '123456789012';

        $return = [];

        $this->apiClientProphecy
            ->httpPut('/v1/lpas/' . $lpaId . '/codes', ['code' => $viewerCode])
            ->willReturn($return);

        $viewerCodeService = new ViewerCodeService($this->apiClientProphecy->reveal());

        $codeData = $viewerCodeService->cancelShareCode(self::IDENTITY_TOKEN, $lpaId, $viewerCode);

        $this->assertEquals(null,$codeData);
    }

    /** @test */
    public function it_orders_viewercode_by_order_of_added_date()
    {
        $lpaId = '98765432-01234-01234-01234-012345678901';
        $expiry     = (new \DateTime('now +30 days'))->format('c');

        $shareCodes = [
            0 => [
                'ActorId' => 123,
                'CreatedBy' => self::FIRST_NAME . ' ' . self::SUR_NAME,
                'Added' => '2020-09-16 22:00:00',
                'ViewerCode' => 'ABCD',
                'Organisation' => 'TestOrg1'
            ],
            1 => [
                'ActorId' => self::ACTOR_ID,
                'CreatedBy' => self::FIRST_NAME . ' ' . self::SUR_NAME,
                'Added' => '2020-09-16 22:10:00',
                'ViewerCode' => 'WXYZ',
                'Organisation' => 'TestOrg2'
            ],
            2 => [
                'ActorId' => self::ACTOR_ID,
                'CreatedBy' => self::FIRST_NAME . ' ' . self::SUR_NAME,
                'Added' => '2020-09-16 22:20:00',
                'ViewerCode' => 'LMNO',
                'Organisation' => 'TestOrg3'
            ]
        ];

        $this->apiClientProphecy
            ->httpGet('/v1/lpas/' . $lpaId . '/codes')
            ->willReturn($shareCodes);

        $viewerCodeService = new ViewerCodeService($this->apiClientProphecy->reveal());

        $codeData = $viewerCodeService->getShareCodes(self::IDENTITY_TOKEN, $lpaId, false, self::SORT_ADDED);
        $codeData = $codeData->getArrayCopy();

        $this->assertIsArray($codeData);
        $this->assertNotEquals($shareCodes, $codeData);
        $this->assertEquals($codeData[0]['Added'], $shareCodes[2]['Added']);
        $this->assertEquals( $codeData[1]['Added'], $shareCodes[1]['Added']);
        $this->assertEquals($codeData[2]['Added'], $shareCodes[0]['Added']);
    }
}