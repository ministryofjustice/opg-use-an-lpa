<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa\ResolveActor;

use App\Service\Lpa\ResolveActor\ActorType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ActorTypeTest extends TestCase
{
    #[Test]
    public function it_has_expected_values(): void
    {
        $this->assertEquals('primary-attorney', ActorType::ATTORNEY->value);
        $this->assertEquals('trust-corporation', ActorType::TRUST_CORPORATION->value);
        $this->assertEquals('donor', ActorType::DONOR->value);
    }
}
