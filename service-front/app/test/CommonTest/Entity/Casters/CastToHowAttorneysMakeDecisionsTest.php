<?php

declare(strict_types=1);

namespace CommonTest\Entity\Casters;

use Common\Entity\Casters\CastToHowAttorneysMakeDecisions;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CastToHowAttorneysMakeDecisionsTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private CastToHowAttorneysMakeDecisions $castToHowAttorneysMakeDecisions;

    public function setUp(): void
    {
        $this->mockHydrator                    = $this->createMock(ObjectMapper::class);
        $this->castToHowAttorneysMakeDecisions = new CastToHowAttorneysMakeDecisions();
    }

    #[Test]
    public function can_cast_how_attorneys_make_decisions(): void
    {
        $howAttorneysMakeDecisions = 'singular';

        $expectedhowAttorneysMakeDecisions = 'singular';

        $result = $this->castToHowAttorneysMakeDecisions->cast($howAttorneysMakeDecisions, $this->mockHydrator);

        $this->assertEquals($expectedhowAttorneysMakeDecisions, $result);
    }
}
