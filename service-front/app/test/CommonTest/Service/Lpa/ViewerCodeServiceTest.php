<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Service\ApiClient\Client;
use Common\Service\Context\UserContext;
use Common\Service\Lpa\ViewerCodeService;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ViewerCodeService::class)]
class ViewerCodeServiceTest extends TestCase
{
    private const IDENTITY_TOKEN = '01234567-01234-01234-01234-012345678901';
    private const SORT_ADDED     = 'Added';
    private const LPA_ID         = '98765432-12345-54321-12345-9876543210';
    private const ACTOR_ID       = 10;
    private const FIRST_NAME     = 'John';
    private const SUR_NAME       = 'Will';
    private const CSRF_CODE      = '1234';

    private Client&MockObject $apiClientProphecy;

    public function setUp(): void
    {
        $this->apiClientProphecy = $this->createMock(Client::class);

        // all methods in this
        $this->apiClientProphecy
            ->expects($this->once())->method('setUserContext')->with(new UserContext(self::IDENTITY_TOKEN));
    }

    #[Test]
    public function it_creates_a_new_viewercode_given_correct_details(): void
    {
        $lpaId      = '700000000047';
        $viewerCode = '123456789012';
        $expiry     = (new DateTime('now +50 days'))->format('c');
        $orgName    = 'Test Org';

        $return = [
            'code'         => $viewerCode,
            'expiry'       => $expiry,
            'organisation' => $orgName,
        ];

        $this->apiClientProphecy
            ->method('httpPost')->with('/v1/lpas/' . $lpaId . '/codes', ['organisation' => $orgName])
            ->willReturn($return);

        $viewerCodeService = new ViewerCodeService($this->apiClientProphecy);

        $codeData = $viewerCodeService->createShareCode(self::IDENTITY_TOKEN, $lpaId, $orgName);

        $this->assertInstanceOf(ArrayObject::class, $codeData);
        $this->assertEquals($viewerCode, $codeData->code);
        $this->assertInstanceOf(DateTime::class, new DateTime($codeData->expiry));
        $this->assertEquals($orgName, $codeData->organisation);
    }

    #[Test]
    public function it_gets_share_codes_for_a_given_lpa(): void
    {
        $lpaId = '98765432-01234-01234-01234-012345678901';

        $return = [
            [
                'UserLpaActor' => $lpaId,
            ],
        ];

        $this->apiClientProphecy
            ->method('httpGet')->with('/v1/lpas/' . $lpaId . '/codes')
            ->willReturn($return);

        $viewerCodeService = new ViewerCodeService($this->apiClientProphecy);

        $shareCodes = $viewerCodeService->getShareCodes(self::IDENTITY_TOKEN, $lpaId, false);

        $this->assertInstanceOf(ArrayObject::class, $shareCodes);
        $this->assertEquals($lpaId, $shareCodes[0]['UserLpaActor']);
    }

    #[Test]
    public function returns_empty_array_if_no_share_codes_generated(): void
    {
        $lpaId = '98765432-01234-01234-01234-012345678902';

        $this->apiClientProphecy
            ->method('httpGet')->with('/v1/lpas/' . $lpaId . '/codes')
            ->willReturn([]);

        $viewerCodeService = new ViewerCodeService($this->apiClientProphecy);

        $shareCodes = $viewerCodeService->getShareCodes(self::IDENTITY_TOKEN, $lpaId, false);

        $this->assertInstanceOf(ArrayObject::class, $shareCodes);
        $this->assertEmpty($shareCodes);
    }

    #[Test]
    public function gets_number_of_active_codes_for_lpa(): void
    {
        $lpaId = '98765432-01234-01234-01234-012345678902';

        $endOfToday      =  (new DateTime('now'))->setTime(23, 59, 59)->format('c');
        $currentDateTime =  (new DateTime('now'))->setTime(16, 59, 59)->format('c');
        $futureWeek      = (new DateTime('+1 week'))->format('c');
        $pastWeek        = (new DateTime('-1 week'))->format('c');

        $return = [
            [
                'Added'        => '2020-09-16 22:00:00',
                'UserLpaActor' => $lpaId,
                'Expires'      => $pastWeek,
            ],
            [
                'Added'        => '2020-09-16 22:00:00',
                'UserLpaActor' => $lpaId,
                'Expires'      => $futureWeek,
            ],
            [
                'Added'        => '2020-09-16 22:00:00',
                'UserLpaActor' => $lpaId,
                'Expires'      => $endOfToday,
            ],
        ];

        $this->apiClientProphecy
            ->method('httpGet')->with('/v1/lpas/' . $lpaId . '/codes')
            ->willReturn($return);

        $viewerCodeService = new ViewerCodeService($this->apiClientProphecy);

        $shareCodes = $viewerCodeService->getShareCodes(self::IDENTITY_TOKEN, $lpaId, true);

        $this->assertInstanceOf(ArrayObject::class, $shareCodes);
        $this->assertEquals($lpaId, $shareCodes[0]['UserLpaActor']);
        $this->assertLessThan($currentDateTime, $shareCodes[0]['Expires']);
        $this->assertGreaterThan($currentDateTime, $shareCodes[1]['Expires']);
        $this->assertGreaterThan($currentDateTime, $shareCodes[2]['Expires']);
        $this->assertEquals(2, $shareCodes['activeCodeCount']);
    }

    #[Test]
    public function it_cancels_a_new_viewercode_given_correct_details(): void
    {
        $lpaId      = '700000000047';
        $viewerCode = '123456789012';

        $return = [];

        $this->apiClientProphecy
            ->method('httpPut')->with('/v1/lpas/' . $lpaId . '/codes', ['code' => $viewerCode])
            ->willReturn($return);

        $viewerCodeService = new ViewerCodeService($this->apiClientProphecy);

        $codeData = $viewerCodeService->cancelShareCode(self::IDENTITY_TOKEN, $lpaId, $viewerCode);

        $this->assertEquals(null, $codeData);
    }

    #[Test]
    public function it_orders_viewercode_by_order_of_added_date(): void
    {
        $lpaId  = '98765432-01234-01234-01234-012345678901';
        $expiry = (new DateTime('now +50 days'))->format('c');

        $shareCodes = [
            0 => [
                'ActorId'      => 123,
                'CreatedBy'    => self::FIRST_NAME . ' ' . self::SUR_NAME,
                'Added'        => '2020-09-16 22:00:00',
                'ViewerCode'   => 'ABCD',
                'Organisation' => 'TestOrg1',
            ],
            1 => [
                'ActorId'      => self::ACTOR_ID,
                'CreatedBy'    => self::FIRST_NAME . ' ' . self::SUR_NAME,
                'Added'        => '2020-09-16 22:10:00',
                'ViewerCode'   => 'WXYZ',
                'Organisation' => 'TestOrg2',
            ],
            2 => [
                'ActorId'      => self::ACTOR_ID,
                'CreatedBy'    => self::FIRST_NAME . ' ' . self::SUR_NAME,
                'Added'        => '2020-09-16 22:20:00',
                'ViewerCode'   => 'LMNO',
                'Organisation' => 'TestOrg3',
            ],
        ];

        $this->apiClientProphecy
            ->method('httpGet')->with('/v1/lpas/' . $lpaId . '/codes')
            ->willReturn($shareCodes);

        $viewerCodeService = new ViewerCodeService($this->apiClientProphecy);

        $codeData = $viewerCodeService->getShareCodes(self::IDENTITY_TOKEN, $lpaId, false, self::SORT_ADDED);
        $codeData = $codeData->getArrayCopy();

        $this->assertIsArray($codeData);
        $this->assertNotEquals($shareCodes, $codeData);
        $this->assertEquals($codeData[0]['Added'], $shareCodes[2]['Added']);
        $this->assertEquals($codeData[1]['Added'], $shareCodes[1]['Added']);
        $this->assertEquals($codeData[2]['Added'], $shareCodes[0]['Added']);
    }
}
