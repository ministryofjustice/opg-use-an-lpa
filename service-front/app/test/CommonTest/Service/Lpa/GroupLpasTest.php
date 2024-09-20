<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Common\Service\Lpa\GroupLpas;

#[CoversClass(GroupLpas::class)]
class GroupLpasTest extends LpaFixtureTestCase
{
    #[Test]
    public function it_groups_lpas_by_donor(): void
    {
        $lpas = $this->lpaFixtureData();

        $grouper     = new GroupLpas();
        $groupedLpas = $grouper($lpas)->getArrayCopy();

        $this->assertEquals(5, count($groupedLpas));
        $this->assertArrayHasKey('Amy Johnson 1980-01-01', $groupedLpas);
        $this->assertArrayHasKey('Gemma Taylor 1980-01-01', $groupedLpas);
        $this->assertArrayHasKey('Gemma Taylor 1998-02-09', $groupedLpas);
        $this->assertArrayHasKey('Sam Taylor 1980-01-01', $groupedLpas);
        $this->assertArrayHasKey('Daniel Williams 1980-01-01', $groupedLpas);

        $this->assertEquals(2, count($groupedLpas['Amy Johnson 1980-01-01']));
        $this->assertEquals(1, count($groupedLpas['Gemma Taylor 1980-01-01']));
        $this->assertEquals(1, count($groupedLpas['Gemma Taylor 1998-02-09']));
        $this->assertEquals(2, count($groupedLpas['Sam Taylor 1980-01-01']));
        $this->assertEquals(3, count($groupedLpas['Daniel Williams 1980-01-01']));
    }
}
