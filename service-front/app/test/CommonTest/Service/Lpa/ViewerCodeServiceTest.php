<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use ArrayObject;
use Common\Service\ApiClient\Client;
use Common\Service\Lpa\ViewerCodeService;
use PHPUnit\Framework\TestCase;

class ViewerCodeServiceTest extends TestCase
{
    const IDENTITY_TOKEN = '01234567-01234-01234-01234-012345678901';

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
}