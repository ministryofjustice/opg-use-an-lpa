<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Service\InternalClock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InternalClock::class)]
class InternalClockTest extends TestCase
{
    #[Test]
    public function Now(): void
    {
        $sut = new InternalClock();

        $actual = $sut->now();

        $this->assertEqualsWithDelta(time(), $actual->getTimestamp(), 1);
    }
}
