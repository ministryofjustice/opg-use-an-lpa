<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\GetAttorneyStatus;
use App\Service\Lpa\ResolveActor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class ResolveActorTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;
    private GetAttorneyStatus|ObjectProphecy $getAttorneyStatusProphecy;

    public function setUp(): void
    {
        $this->getAttorneyStatusProphecy = $this->prophesize(GetAttorneyStatus::class);
    }

    private function getActorResolver(): ResolveActor
    {
        return new ResolveActor();
    }

    #[Test]
    public function can_find_actor_who_is_a_donor(): void
    {
        $lpa = [
            'donor' => [
                'id'  => 1,
                'uId' => '123456789012',
            ],
        ];

        $resolver = $this->getActorResolver();

        $result = $resolver($lpa, 1);

        $this->assertEquals(
            [
                'type'    => 'donor',
                'details' => $lpa['donor'],
            ],
            $result
        );

        $result = $resolver($lpa, 123456789012);

        $this->assertEquals(
            [
                'type'    => 'donor',
                'details' => $lpa['donor'],
            ],
            $result
        );
    }

    #[Test]
    public function can_find_actor_who_is_a_donor_by_linked_id(): void
    {
        $lpa = [
            'donor' => [
                'id'     => 1,
                'uId'    => '123456789013',
                'linked' => [['id' => 1, 'uId' => '123456789013'], ['id' => 2, 'uId' => '123456789012']],
            ],
        ];

        $resolver = $this->getActorResolver();

        $result = $resolver($lpa, 2);

        $this->assertEquals(
            [
                'type'    => 'donor',
                'details' => $lpa['donor'],
            ],
            $result
        );
    }

    #[Test]
    public function can_find_actor_who_is_a_donor_by_linked_uid(): void
    {
        $lpa = [
            'donor' => [
                'id'     => 1,
                'uId'    => '123456789013',
                'linked' => [['id' => 1, 'uId' => '123456789013'], ['id' => 2, 'uId' => '123456789012']],
            ],
        ];

        $resolver = $this->getActorResolver();

        $result = $resolver($lpa, 123456789012);

        $this->assertEquals(
            [
                'type'    => 'donor',
                'details' => $lpa['donor'],
            ],
            $result
        );
    }

    #[Test]
    public function can_not_find_actor_who_is_not_a_donor_by_linked_id(): void
    {
        $lpa = [
            'donor' => [
                'id'     => 1,
                'uId'    => '123456789013',
                'linked' => [['id' => 1, 'uId' => '123456789013'], ['id' => 2, 'uId' => '123456789012']],
            ],
        ];

        $resolver = $this->getActorResolver();

        $result = $resolver($lpa, 3);

        $this->assertNull($result);
    }

    #[Test]
    public function can_not_find_actor_who_is_not_a_donor_by_linked_uid(): void
    {
        $lpa = [
            'donor' => [
                'id'     => 1,
                'uId'    => '123456789013',
                'linked' => [['id' => 1, 'uId' => '123456789013'], ['id' => 2, 'uId' => '123456789012']],
            ],
        ];

        $resolver = $this->getActorResolver();

        $result = $resolver($lpa, 123456789999);

        $this->assertNull($result);
    }

    #[Test]
    public function can_find_actor_who_is_an_attorney(): void
    {
        $lpa = [
            'donor'     => [
                'id' => 1,
            ],
            'attorneys' => [
                ['id' => 1, 'uId' => '123456789012', 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                ['id' => 3, 'uId' => '234567890123', 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                ['id' => 7, 'uId' => '345678901234', 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
            ],
        ];

        $this->getAttorneyStatusProphecy
            ->__invoke(
                [
                    'id'           => 3,
                    'uId'          => '234567890123',
                    'firstname'    => 'A',
                    'surname'      => 'B',
                    'systemStatus' => true,
                ]
            )
            ->willReturn(0);

        $resolver = $this->getActorResolver();

        $result = $resolver($lpa, 3);

        $this->assertEquals(
            [
                'type'    => 'primary-attorney',
                'details' => [
                    'id'           => 3,
                    'uId'          => '234567890123',
                    'firstname'    => 'A',
                    'surname'      => 'B',
                    'systemStatus' => true,
                ],
            ],
            $result
        );

        $result = $resolver($lpa, 234567890123);

        $this->assertEquals(
            [
                'type'    => 'primary-attorney',
                'details' => [
                    'id'           => 3,
                    'uId'          => '234567890123',
                    'firstname'    => 'A',
                    'surname'      => 'B',
                    'systemStatus' => true,
                ],
            ],
            $result
        );
    }

    #[Test]
    #[DataProvider('ghostAttorneyDataProvider')]
    public function can_not_find_actor_who_is_a_ghost_attorney(int $actorId, array $attorneyData): void
    {
        $lpa = [
            'donor'              => [
                'id'  => 1,
                'uId' => '456789012345',
            ],
            'original_attorneys' => [
                ['id' => 2, 'uId' => '123456789012', 'systemStatus' => true],
                ['id' => 3, 'uId' => '234567890123', 'firstname' => 'A', 'systemStatus' => true],
                ['id' => 7, 'uId' => '345678901234', 'surname' => 'B', 'systemStatus' => true],
            ],
        ];

        $this->getAttorneyStatusProphecy
            ->__invoke($attorneyData)
            ->willReturn(1);

        $resolver = $this->getActorResolver();

        $result = $resolver($lpa, $actorId);
        $this->assertNull($result);
    }

    public static function ghostAttorneyDataProvider(): array
    {
        return [
            [
                2,
                ['id' => 2, 'uId' => '123456789012', 'systemStatus' => true],
            ],
            [
                123456789012,
                ['id' => 2, 'uId' => '123456789012', 'systemStatus' => true],
            ],
            [
                3,
                ['id' => 3, 'uId' => '234567890123', 'firstname' => 'A', 'systemStatus' => true],
            ],
            [
                234567890123,
                ['id' => 3, 'uId' => '234567890123', 'firstname' => 'A', 'systemStatus' => true],
            ],
            [
                7,
                ['id' => 7, 'uId' => '345678901234', 'surname' => 'B', 'systemStatus' => true],
            ],
            [
                345678901234,
                ['id' => 7, 'uId' => '345678901234', 'surname' => 'B', 'systemStatus' => true],
            ],
        ];
    }

    #[Test]
    public function can_not_find_actor_who_is_an_inactive_attorney(): void
    {
        $lpa = [
            'donor'              => [
                'id'  => 1,
                'uId' => '456789012345',
            ],
            'original_attorneys' => [
                ['id' => 1, 'uId' => '123456789012', 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
                ['id' => 3, 'uId' => '234567890123', 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => false],
                ['id' => 7, 'uId' => '345678901234', 'firstname' => 'A', 'surname' => 'B', 'systemStatus' => true],
            ],
        ];

        $this->getAttorneyStatusProphecy
            ->__invoke(
                [
                    'id'           => 3,
                    'uId'          => '234567890123',
                    'firstname'    => 'A',
                    'surname'      => 'B',
                    'systemStatus' => false,
                ],
            )
            ->willReturn(2);

        $resolver = $this->getActorResolver();

        $result = $resolver($lpa, 3);
        $this->assertNull($result);

        $result = $resolver($lpa, 234567890123);
        $this->assertNull($result);
    }

    #[Test]
    public function can_find_actor_who_is_a_trust_corporation(): void
    {
        $lpa = [
            'donor'             => [
                'id' => 1,
            ],
            'trustCorporations' => [
                [
                    'id'           => 9,
                    'uId'          => '700000151998',
                    'firstname'    => 'trust',
                    'surname'      => 'corporation',
                    'companyName'  => 'trust corporation ltd',
                    'systemStatus' => true,
                ],
            ],
        ];

        $resolver = $this->getActorResolver();

        $result = $resolver($lpa, 700000151998);

        $this->assertEquals(
            [
                'type'    => 'trust-corporation',
                'details' => [
                    'id'           => 9,
                    'uId'          => '700000151998',
                    'firstname'    => 'trust',
                    'surname'      => 'corporation',
                    'companyName'  => 'trust corporation ltd',
                    'systemStatus' => true,
                ],
            ],
            $result
        );

        $result = $resolver($lpa, 9);

        $this->assertEquals(
            [
                'type'    => 'trust-corporation',
                'details' => [
                    'id'           => 9,
                    'uId'          => '700000151998',
                    'firstname'    => 'trust',
                    'surname'      => 'corporation',
                    'companyName'  => 'trust corporation ltd',
                    'systemStatus' => true,
                ],
            ],
            $result
        );
    }
}
