<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use EventSauce\ObjectHydrator\ObjectMapper;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CastToWhenTheLpaCanBeUsedTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private CastToWhenTheLpaCanBeUsed $castToWhenTheLpaCanBeUsed;

    public function setUp(): void
    {
        $this->mockHydrator              = $this->createMock(ObjectMapper::class);
        $this->castToWhenTheLpaCanBeUsed = new CastToWhenTheLpaCanBeUsed();
    }

    #[DataProvider('whenTheLpaCanBeUsedProvider')]
    #[Test]
    public function can_cast_to_when_lpa_can_be_used($whenLpaCanBeUsed, $expectedWhenLpaCanBeUsed): void
    {
        $result = $this->castToWhenTheLpaCanBeUsed->cast($whenLpaCanBeUsed, $this->mockHydrator);

        $this->assertEquals($expectedWhenLpaCanBeUsed, $result);
    }

    public static function whenTheLpaCanBeUsedProvider(): Iterator
    {
        yield [
            'when registered',
            'when-has-capacity',
        ];
        yield [
            'loss of capacity',
            'when-capacity-lost',
        ];
        yield [
            '',
            '',
        ];
        yield [
            'invalid value',
            '',
        ];
    }
}
