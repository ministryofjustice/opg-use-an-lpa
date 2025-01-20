<?php

declare(strict_types=1);

namespace CommonTest\Entity\Casters;

use Common\Entity\Casters\CastToCaseSubtype;
use EventSauce\ObjectHydrator\ObjectMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CastToCaseSubtypeTest extends TestCase
{
    private ObjectMapper $mockHydrator;
    private CastToCaseSubtype $castToCaseSubtype;

    public function setUp(): void
    {
        $this->mockHydrator      = $this->createMock(ObjectMapper::class);
        $this->castToCaseSubtype = new CastToCaseSubtype();
    }

    #[Test]
    public function can_cast_case_subtype(): void
    {
        $caseSubType = 'personal-welfare';

        $expectedCaseSubType = 'hw';

        $result = $this->castToCaseSubtype->cast($caseSubType, $this->mockHydrator);

        $this->assertEquals($expectedCaseSubType, $result);
    }
}
