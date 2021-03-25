<?php

namespace AppTest\Service\Lpa;

use App\Service\Lpa\LpaAlreadyAdded;
use App\Service\Lpa\LpaService;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class LpaAlreadyAddedTest extends TestCase
{
    /** @var ObjectProphecy|LpaService */
    private $lpaServiceProphecy;

    /** @var ObjectProphecy|LoggerInterface */
    private $loggerProphecy;

    private string $userId;
    private string $lpaUid;
    private string $userLpaActorToken;

    public function setUp()
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);

        $this->userId = '12345';
        $this->lpaUid = '700000000543';
        $this->userLpaActorToken = 'abc123-456rtp';
    }

    private function getLpaAlreadyAddedService(): LpaAlreadyAdded
    {
        return new LpaAlreadyAdded(
            $this->lpaServiceProphecy->reveal(),
            $this->loggerProphecy->reveal(),
        );
    }
    public function test_returns_null_if_lpa_not_already_added()
    {
        $this->lpaServiceProphecy
            ->getAllForUser('12345')
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

    public function test_returns_lpa_data_if_lpa_is_already_added()
    {
        $this->lpaServiceProphecy
            ->getAllForUser($this->userId)
            ->willReturn(
                [
                    'xyz321-987ltc' => [
                        'user-lpa-actor-token' => 'xyz321-987ltc',
                        'lpa' => [
                            'uId' => '700000000111'
                        ],
                    ],
                    $this->userLpaActorToken => [
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'lpa' => [
                            'uId' => $this->lpaUid
                        ],
                    ]
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertEquals([
            'user-lpa-actor-token' => $this->userLpaActorToken,
            'lpa' => [
                'uId' => $this->lpaUid
            ],
        ], $lpaAddedData);
    }
}
