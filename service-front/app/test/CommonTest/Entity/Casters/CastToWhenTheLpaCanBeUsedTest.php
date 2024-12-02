<?php

declare(strict_types=1);

namespace CommonTest\Entity\Casters;

use Common\Entity\Casters\CastToHowAttorneysMakeDecisions;
use Common\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use ValueError;

class CastToWhenTheLpaCanBeUsedTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    #[DataProvider('whenCanTheLpaBeUsedProvider')]
    public function can_cast_how_attorneys_make_decisions($input, $expectedOutput): void
    {
        $castToWhenTheLpaCanBeUsed = new CastToWhenTheLpaCanBeUsed();

        $this->assertEquals(
            $expectedOutput,
            $castToWhenTheLpaCanBeUsed->cast(
                $input,
                $this->prophesize(ObjectMapper::class)->reveal()
            )
        );
    }

    public function whenCanTheLpaBeUsedProvider(): array
    {
        return [
            ['when registered', 'when-has-capacity'],
            ['when-has-capacity', 'when-has-capacity'],
            ['loss of capacity', 'when-capacity-lost'],
            ['when-capacity-lost', 'when-capacity-lost'],
            ['', ''],
            ['unexpected', ''],
        ];
    }

    #[Test]
    public function throws_exception_on_invalid_type()
    {
        $castToHowAttorneysMakeDecisions = new CastToHowAttorneysMakeDecisions();

        $this->expectException(ValueError::class);

        $castToHowAttorneysMakeDecisions->cast(
            'not-a-valid-type',
            $this->prophesize(ObjectMapper::class)->reveal()
        );
    }
}
