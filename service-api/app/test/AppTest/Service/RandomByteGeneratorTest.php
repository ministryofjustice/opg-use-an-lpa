<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Service\RandomByteGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RandomByteGeneratorTest extends TestCase
{
    #[Test]
    public function it_returns_random_bytes(): void
    {
        $sut = new RandomByteGenerator();

        $this->assertEquals(15, strlen(($sut)(15)));
    }
}
