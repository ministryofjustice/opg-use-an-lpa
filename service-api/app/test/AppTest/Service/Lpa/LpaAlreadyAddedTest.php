<?php

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Service\Features\FeatureEnabled;
use App\Service\Lpa\LpaAlreadyAdded;
use App\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \App\Service\Lpa\LpaAlreadyAdded
 */
class LpaAlreadyAddedTest extends TestCase
{
    /** @var ObjectProphecy|LpaService */
    private $lpaServiceProphecy;

    /** @var ObjectProphecy|UserLpaActorMapInterface */
    private ObjectProphecy $userLpaActorMapProphecy;

    /** @var ObjectProphecy|FeatureEnabled */
    private $featureEnabledProphecy;

    /** @var ObjectProphecy|LoggerInterface */
    private $loggerProphecy;

    private string $userId;
    private string $lpaUid;
    private string $userLpaActorToken;

    public function setUp()
    {
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
        $this->userLpaActorMapProphecy = $this->prophesize(UserLpaActorMapInterface::class);
        $this->featureEnabledProphecy = $this->prophesize(FeatureEnabled::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);

        $this->userId = '12345';
        $this->lpaUid = '700000000543';
        $this->userLpaActorToken = 'abc123-456rtp';
    }

    private function getLpaAlreadyAddedService(): LpaAlreadyAdded
    {
        return new LpaAlreadyAdded(
            $this->lpaServiceProphecy->reveal(),
            $this->userLpaActorMapProphecy->reveal(),
            $this->featureEnabledProphecy->reveal(),
            $this->loggerProphecy->reveal(),
        );
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function returns_null_if_lpa_not_already_added_with_other_lpas_in_account()
    {
        $this->lpaServiceProphecy
            ->getAllActivatedLpasForUser('12345')
            ->willReturn(
                [
                    $this->userLpaActorToken => [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'lpa' => [
                            'uid' => $this->lpaUid
                        ],
                    ],
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, '700000000321');
        $this->assertNull($lpaAddedData);
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function returns_null_if_lpa_not_already_added()
    {
        $this->lpaServiceProphecy
            ->getAllActivatedLpasForUser($this->userId)
            ->willReturn([]);

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, '700000000321');
        $this->assertNull($lpaAddedData);
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function returns_null_if_lpa_added_but_not_usable_found_in_api()
    {
        $this->userLpaActorMapProphecy
            ->getUsersLpas($this->userId)
            ->willReturn(
                [
                    [
                        'Id' => $this->userLpaActorToken,
                        'SiriusUid' => $this->lpaUid,
                    ],
                ]
            );

        $this->lpaServiceProphecy
            ->getAllActivatedLpasForUser($this->userId)
            ->willReturn([]);

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertNull($lpaAddedData);
    }

    /**
     * @test
     * @covers ::__invoke
     */
    public function returns_lpa_data_if_lpa_is_already_added()
    {
        $this->lpaServiceProphecy
            ->getAllActivatedLpasForUser($this->userId)
            ->willReturn(
                [
                    'xyz321-987ltc' => [
                        'user-lpa-actor-token' => 'xyz321-987ltc',
                        'lpa' => [
                            'uId' => '700000000111',
                            'caseSubtype' => 'pfa',
                            'donor' => [
                                'uId' => '700000000222',
                                'firstname'     => 'Some',
                                'middlenames'   => '',
                                'surname'       => 'Person'
                            ],
                        ],
                    ],
                    $this->userLpaActorToken => [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'lpa' => [
                            'uId' => $this->lpaUid,
                            'caseSubtype' => 'hw',
                            'donor' => [
                                'uId' => '700000000444',
                                'firstname'     => 'Another',
                                'middlenames'   => '',
                                'surname'       => 'Person',
                            ],
                        ],
                    ]
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertEquals([
            'donor'         => [
                'uId'           => '700000000444',
                'firstname'     => 'Another',
                'middlenames'   => '',
                'surname'       => 'Person',
            ],
            'caseSubtype' => 'hw',
            'lpaActorToken' => $this->userLpaActorToken
        ], $lpaAddedData);
    }
}
