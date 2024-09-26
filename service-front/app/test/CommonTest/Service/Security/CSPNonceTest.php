<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Common\Service\Security\CSPNonce;
use PHPUnit\Framework\TestCase;

#[CoversClass(CSPNonce::class)]
class CSPNonceTest extends TestCase
{
    #[Test]
    public function it_can_be_supplied_a_set_value(): void
    {
        $sut = new CSPNonce('test');

        $this->assertEquals('test', (string) $sut);
    }

    #[Test]
    public function it_will_create_a_secure_value_if_not_set(): void
    {
        $sut = new CSPNonce();

        $this->assertMatchesRegularExpression('/[a-z0-9]{32}/', (string) $sut);
    }
}
