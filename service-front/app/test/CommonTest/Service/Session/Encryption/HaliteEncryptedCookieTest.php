<?php

declare(strict_types=1);

namespace CommonTest\Service\Session\Encryption;

use Common\Service\Session\Encryption\HaliteCrypto;
use Common\Service\Session\Encryption\HaliteEncryptedCookie;
use Common\Service\Session\KeyManager\Key;
use Common\Service\Session\KeyManager\KeyManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class HaliteEncryptedCookieTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $keyManagerStub = $this->createStub(KeyManagerInterface::class);
        $cryptoStub     = $this->createStub(HaliteCrypto::class);
        $loggerStub     = $this->createStub(LoggerInterface::class);

        $this->expectNotToPerformAssertions();
        new HaliteEncryptedCookie($keyManagerStub, $cryptoStub, $loggerStub);
    }

    #[Test]
    public function it_encodes_a_session_array(): void
    {
        $data = [
            'session' => 'data',
        ];

        $keyMock = $this->createMock(Key::class);
        $keyMock->method('getId')->willReturn('1');

        $keyManagerStub = $this->createStub(KeyManagerInterface::class);
        $keyManagerStub->method('getEncryptionKey')->willReturn($keyMock);

        $cryptoStub = $this->createStub(HaliteCrypto::class);
        $cryptoStub->method('encrypt')->willReturn('encryptedString');

        $loggerStub = $this->createStub(LoggerInterface::class);

        $sut = new HaliteEncryptedCookie($keyManagerStub, $cryptoStub, $loggerStub);

        $encoded = $sut->encodeCookieValue($data);
        $this->assertEquals('1.ZW5jcnlwdGVkU3RyaW5n', $encoded);
    }

    #[Test]
    public function it_encodes_an_empty_array_of_data_to_a_blank_string(): void
    {
    }

    #[Test]
    public function it_decodes_a_string_into_session_data(): void
    {
    }

    #[Test]
    public function it_throws_an_exception_when_key_id_not_matched_and_returns_new_session(): void
    {
    }

    #[Test]
    public function it_decodes_an_empty_string_into_an_empty_array(): void
    {
    }
}
