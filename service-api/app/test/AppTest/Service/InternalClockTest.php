<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Service\InternalClock;
use PHPUnit\Framework\TestCase;

class InternalClockTest extends TestCase
{

    /**
     * @test
     * @covers \App\Service\InternalClock::now
     */
    public function Now(): void
    {
        $sut = new InternalClock();

        $actual = $sut->now();

        $this->assertEqualsWithDelta(time(), $actual->getTimestamp(), 1);
    }
}
