<?php

declare(strict_types=1);

namespace CommonTest\Enum;

use Common\Enum\Channel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ChannelTest extends TestCase
{
    #[Test]
    public function test_lpa_type_enum_has_issers(): void
    {
        $paperChannel  = Channel::PAPER;
        $onlineChannel = Channel::ONLINE;

        $this->assertTrue($paperChannel->isPaperChannel());
        $this->assertFalse($paperChannel->isOnlineChannel());

        $this->assertTrue($onlineChannel->isOnlineChannel());
        $this->assertFalse($onlineChannel->isPaperChannel());
    }
}
