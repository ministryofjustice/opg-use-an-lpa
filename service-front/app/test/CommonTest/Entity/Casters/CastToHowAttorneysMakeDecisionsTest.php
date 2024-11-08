<?php

declare(strict_types=1);

namespace CommonTest\Entity\Casters;

use Common\Entity\Casters\CastToHowAttorneysMakeDecisions;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use ValueError;


class CastToHowAttorneysMakeDecisionsTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    #[DataProvider('howAttorneysMakeDecisionsProvider')]
    public function can_cast_how_attorneys_make_decisions($howMakeDecision): void
    {
        $castToHowAttorneysMakeDecisions = new CastToHowAttorneysMakeDecisions();

        $this->assertEquals(
            $howMakeDecision,
            $castToHowAttorneysMakeDecisions->cast(
                $howMakeDecision,
                $this->prophesize(ObjectMapper::class)->reveal()
            )
        );
    }

    public function howAttorneysMakeDecisionsProvider(): array
    {
        return [
            ['singular'],
            ['jointly'],
            ['jointly-and-severally'],
            ['jointly-for-some-severally-for-others'],
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
