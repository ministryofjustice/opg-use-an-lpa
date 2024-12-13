<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Casters\CastToAttorneyActDecisions;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CastToWhenTheLpaCanBeUsedTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private CastToAttorneyActDecisions $castToWhenTheLpaCanBeUsed;

    public function setUp(): void
    {
        $this->mockHydrator              = $this->createMock(ObjectMapper::class);
        $this->castToWhenTheLpaCanBeUsed = new CastToAttorneyActDecisions();
    }

    #[DataProvider('whenTheLpaCanBeUsedProvider')]
    #[Test]
    public function can_cast_to_when_lpa_can_be_used($whenLpaCanBeUsed, $expectedWhenLpaCanBeUsed): void
    {
        $result = $this->castToWhenTheLpaCanBeUsed->cast($whenLpaCanBeUsed, $this->mockHydrator);

        $this->assertEquals($expectedWhenLpaCanBeUsed, $result);
    }

    public static function whenTheLpaCanBeUsedProvider(): array
    {
        return [
            [
                'when registered',
                'when-has-capacity',
            ],
            [
                'loss of capacity',
                'when-capacity-lost',
            ],
            [
                '',
                '',
            ],
            [
                'invalid value',
                '',
            ]
        ];
    }
}
