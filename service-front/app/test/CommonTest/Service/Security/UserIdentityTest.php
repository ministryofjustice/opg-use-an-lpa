<?php

declare(strict_types=1);

namespace CommonTest\Service\Security;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Common\Service\Security\UserIdentity;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserIdentity::class)]
class UserIdentityTest extends TestCase
{
    #[Test]
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
