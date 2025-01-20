<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Service\Lpa\AddLpa\LpaAlreadyAdded;
use App\Service\Lpa\LpaManagerInterface;
use App\Service\Lpa\LpaService;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(LpaAlreadyAdded::class)]
class LpaAlreadyAddedTest extends TestCase
{
    use ProphecyTrait;

    private LpaManagerInterface|ObjectProphecy $lpaManagerProphecy;
    private UserLpaActorMapInterface|ObjectProphecy $userLpaActorMapProphecy;

    private string $userId;
    private string $lpaUid;
    private string $userLpaActorToken;

    public function setUp(): void
    {
        $this->lpaManagerProphecy      = $this->prophesize(LpaManagerInterface::class);
        $this->userLpaActorMapProphecy = $this->prophesize(UserLpaActorMapInterface::class);

        $this->userId            = '12345';
        $this->lpaUid            = '700000000543';
        $this->userLpaActorToken = 'abc123-456rtp';
    }

    private function getLpaAlreadyAddedService(): LpaAlreadyAdded
    {
        return new LpaAlreadyAdded(
            $this->lpaManagerProphecy->reveal(),
            $this->userLpaActorMapProphecy->reveal(),
        );
    }

    #[Test]
    public function returns_null_if_lpa_not_already_added(): void
    {
        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn([]);

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, '700000000321');
        $this->assertNull($lpaAddedData);
    }

    #[Test]
    public function returns_not_activated_flag_if_lpa_requested_but_not_active(): void
    {
        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id'         => $this->userLpaActorToken,
                        'SiriusUid'  => $this->lpaUid,
                        'ActivateBy' => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
                    ],
                ]
            );

        $this->lpaManagerProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->willReturn(
                [
                    'user-lpa-actor-token' => $this->userLpaActorToken,
                    'lpa'                  => [
                        'uId'                  => $this->lpaUid,
                        'caseSubtype'          => 'hw',
                        'donor'                => [
                            'uId'         => '700000000444',
                            'firstname'   => 'Another',
                            'middlenames' => '',
                            'surname'     => 'Person',
                        ],
                        'activationKeyDueDate' => null,
                    ],
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertEquals(
            [
                'donor'                => [
                    'uId'         => '700000000444',
                    'firstname'   => 'Another',
                    'middlenames' => '',
                    'surname'     => 'Person',
                ],
                'caseSubtype'          => 'hw',
                'lpaActorToken'        => $this->userLpaActorToken,
                'notActivated'         => true,
                'activationKeyDueDate' => null,
            ],
            $lpaAddedData
        );
    }

    #[Test]
    public function returns_null_if_lpa_added_but_not_usable_found_in_api(): void
    {
        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id'        => $this->userLpaActorToken,
                        'SiriusUid' => $this->lpaUid,
                    ],
                ]
            );

        $this->lpaManagerProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->willReturn([]);

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertNull($lpaAddedData);
    }

    #[Test]
    public function returns_lpa_data_if_lpa_is_already_added(): void
    {
        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id'        => $this->userLpaActorToken,
                        'SiriusUid' => $this->lpaUid,
                    ],
                ]
            );

        $this->lpaManagerProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->willReturn(
                [
                    'user-lpa-actor-token' => $this->userLpaActorToken,
                    'lpa'                  => [
                        'uId'         => $this->lpaUid,
                        'caseSubtype' => 'hw',
                        'donor'       => [
                            'uId'         => '700000000444',
                            'firstname'   => 'Another',
                            'middlenames' => '',
                            'surname'     => 'Person',
                        ],
                    ],
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertEquals(
            [
                'donor'                => [
                    'uId'         => '700000000444',
                    'firstname'   => 'Another',
                    'middlenames' => '',
                    'surname'     => 'Person',
                ],
                'caseSubtype'          => 'hw',
                'lpaActorToken'        => $this->userLpaActorToken,
                'activationKeyDueDate' => null,
            ],
            $lpaAddedData
        );
    }

    /**
     * In the interim period before the feature flag for combined format goes live we need to ensure that
     * Modernise records in the database don't blow up code that can't handle them. This shouldn't happen
     * on production at all but causes issues in PR environments and local dev.
     *
     * TODO: This test will likely disappear when this code is touched as a part of UML-3784
     */
    #[Test]
    public function correctly_handles_records_without_sirius_uids_if_lpa_already_added(): void
    {
        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id'     => '0123-01-01-01-01234',
                        'LpaUid' => 'M-1234-1234-1234',
                    ],
                    [
                        'Id'        => $this->userLpaActorToken,
                        'SiriusUid' => $this->lpaUid,
                    ],
                ]
            );

        $this->lpaManagerProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->willReturn(
                [
                    'user-lpa-actor-token' => $this->userLpaActorToken,
                    'lpa'                  => [
                        'uId'         => $this->lpaUid,
                        'caseSubtype' => 'hw',
                        'donor'       => [
                            'uId'         => '700000000444',
                            'firstname'   => 'Another',
                            'middlenames' => '',
                            'surname'     => 'Person',
                        ],
                    ],
                ]
            );

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, $this->lpaUid);
        $this->assertEquals(
            [
                'donor'                => [
                    'uId'         => '700000000444',
                    'firstname'   => 'Another',
                    'middlenames' => '',
                    'surname'     => 'Person',
                ],
                'caseSubtype'          => 'hw',
                'lpaActorToken'        => $this->userLpaActorToken,
                'activationKeyDueDate' => null,
            ],
            $lpaAddedData
        );
    }

    #[Test]
    public function returns_null_if_different_lpa_added(): void
    {
        $this->userLpaActorMapProphecy
            ->getByUserId($this->userId)
            ->willReturn(
                [
                    [
                        'Id'        => $this->userLpaActorToken,
                        'SiriusUid' => $this->lpaUid,
                    ],
                ]
            );

        $this->lpaManagerProphecy
            ->getByUserLpaActorToken($this->userLpaActorToken, $this->userId)
            ->shouldNotBeCalled();

        $lpaAddedData = ($this->getLpaAlreadyAddedService())($this->userId, '712312341234');
        $this->assertNull($lpaAddedData);
    }
}
