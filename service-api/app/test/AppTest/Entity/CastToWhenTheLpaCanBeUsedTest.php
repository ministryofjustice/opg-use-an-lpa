<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Casters\CastToAttorneyActDecisions;
use EventSauce\ObjectHydrator\ObjectMapper;
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

    #[Test]
    public function can_when_lpa_can_be_used(): void
    {
        $whenTheLpaCanBeUsed = 'singular';

        $expectedWhenTheLpaCanBeUsed = 'singular';

        $result = $this->castToWhenTheLpaCanBeUsed->cast($whenTheLpaCanBeUsed, $this->mockHydrator);

        $this->assertEquals($expectedWhenTheLpaCanBeUsed, $result);
    }
}
