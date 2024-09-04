<?php

declare(strict_types=1);

namespace AppTest\Entity;

use App\Entity\Casters\CastToWhenTheLpaCanBeUsed;
use EventSauce\ObjectHydrator\ObjectMapper;
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

    #[Test]
    public function can_when_lpa_can_be_used(): void
    {
        $whenTheLpaCanBeUsed = 'singular';

        $expectedWhenTheLpaCanBeUsed = 'singular';

        $result = $this->castToWhenTheLpaCanBeUsed->cast($whenTheLpaCanBeUsed, $this->mockHydrator);

        $this->assertEquals($expectedWhenTheLpaCanBeUsed, $result);
    }
}
