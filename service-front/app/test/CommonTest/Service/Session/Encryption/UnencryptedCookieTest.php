<?php

declare(strict_types=1);

namespace CommonTest\Service\Session\Encryption;

use PHPUnit\Framework\Attributes\Test;
use Common\Service\Session\Encryption\UnencryptedCookie;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

class UnencryptedCookieTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function usage_logs_critical_error(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy
            ->critical(Argument::type('string'))
            ->shouldBeCalled();

        $sut = new UnencryptedCookie($loggerProphecy->reveal());
    }

    #[Test]
    public function it_base64_encodes_an_array_of_data(): void
    {
        $data = [
            'session' => 'data',
        ];

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $sut = new UnencryptedCookie($loggerProphecy->reveal());

        $cookieValue = $sut->encodeCookieValue($data);

        $this->assertEquals('eyJzZXNzaW9uIjoiZGF0YSJ9', $cookieValue);
    }

    #[Test]
    public function it_base64_encodes_an_empty_array_of_data_to_a_blank_string(): void
    {
        $data = [];

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $sut = new UnencryptedCookie($loggerProphecy->reveal());

        $cookieValue = $sut->encodeCookieValue($data);

        $this->assertEquals('', $cookieValue);
    }

    #[Test]
    public function it_decodes_base64_data_into_an_array(): void
    {
        $data = [
            'session' => 'data',
        ];

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $sut = new UnencryptedCookie($loggerProphecy->reveal());

        $sessionData = $sut->decodeCookieValue('eyJzZXNzaW9uIjoiZGF0YSJ9');

        $this->assertEquals($data, $sessionData);
    }

    #[Test]
    public function it_base64_decodes_an_empty_string_into_a_blank_array(): void
    {
        $data = [];

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $sut = new UnencryptedCookie($loggerProphecy->reveal());

        $sessionData = $sut->decodeCookieValue('');

        $this->assertEquals($data, $sessionData);
    }
}
