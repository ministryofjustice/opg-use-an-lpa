<?php

declare(strict_types=1);

namespace CommonTest\Service\Authentication;

use Common\Entity\User;
use Common\Service\Authentication\LocalAccountLogout;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class LocalAccountLogoutTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_returns_a_survey_redirect_url(): void
    {
        $sut = new LocalAccountLogout();

        $result = $sut->logout($this->prophesize(User::class)->reveal());

        $this->assertSame(LocalAccountLogout::LOGOUT_REDIRECT_URL, $result);
    }
}
