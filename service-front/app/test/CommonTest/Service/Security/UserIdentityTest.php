<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use Common\Service\Security\UserIdentity;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass UserIdentity
 */
class UserIdentityTest extends TestCase
{
    /**
     * @test
     * @covers ::__construct
     * @covers ::hash
     * @covers ::__toString
     */
    public function it_returns_an_expected_hash(): void
    {
        $sut = new UserIdentity('', '', '', '', '');

        $this->assertEquals('da11b962a28412cd40253f6047801b5fc0dd01503b475e88eaa58f332c37c9d5', $sut->hash());
        $this->assertEquals(
            'da11b962a28412cd40253f6047801b5fc0dd01503b475e88eaa58f332c37c9d5',
            (string) $sut,
            'UserIdentity does not implement __toString correctly'
        );
    }
}
