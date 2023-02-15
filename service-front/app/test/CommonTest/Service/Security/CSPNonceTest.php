<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use Common\Service\Security\CSPNonce;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Common\Service\Security\CSPNonce
 */
class CSPNonceTest extends TestCase
{
    /**
     * @test
     * @covers ::__construct
     * @covers ::__toString
     */
    public function it_can_be_supplied_a_set_value(): void
    {
        $sut = new CSPNonce('test');

        $this->assertEquals('test', (string) $sut);
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::__toString
     */
    public function it_will_create_a_secure_value_if_not_set(): void
    {
        $sut = new CSPNonce();

        $this->assertMatchesRegularExpression('/[a-z0-9]{32}/', (string) $sut);
    }
}
