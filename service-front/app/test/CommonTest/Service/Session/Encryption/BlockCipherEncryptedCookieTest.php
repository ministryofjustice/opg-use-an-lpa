<?php

declare(strict_types=1);

namespace CommonTest\Service\Session\Encryption;

use Common\Service\Session\Encryption\BlockCipherEncryptedCookie;
use Common\Service\Session\KeyManager\Key;
use Common\Service\Session\KeyManager\KeyManagerInterface;
use Common\Service\Session\KeyManager\KeyNotFoundException;
use Laminas\Crypt\BlockCipher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BlockCipherEncryptedCookie::class)]
class BlockCipherEncryptedCookieTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $keyManagerStub  = $this->createStub(KeyManagerInterface::class);
        $blockCipherStub = $this->createStub(BlockCipher::class);

        $this->expectNotToPerformAssertions();
        new BlockCipherEncryptedCookie($keyManagerStub, $blockCipherStub);
    }

    #[Test]
    public function it_encodes_a_session_array(): void
    {
        $data = [
            'session' => 'data',
        ];

        $keyMock = $this->createStub(Key::class);
        $keyMock->method('getId')->willReturn('1');

        $keyManagerStub = $this->createStub(KeyManagerInterface::class);
        $keyManagerStub->method('getEncryptionKey')->willReturn($keyMock);

        $cryptoStub = $this->createStub(BlockCipher::class);
        $cryptoStub->method('setKey')->willReturnSelf();
        $cryptoStub->method('encrypt')->willReturn('encryptedString');

        $sut = new BlockCipherEncryptedCookie($keyManagerStub, $cryptoStub);

        $encoded = $sut->encodeCookieValue($data);

        $this->assertEquals('1.ZW5jcnlwdGVkU3RyaW5n', $encoded);
    }

    #[Test]
    public function it_encodes_an_empty_array_of_data_to_a_blank_string(): void
    {
        $data = [];

        $keyManagerStub  = $this->createStub(KeyManagerInterface::class);
        $blockCipherStub = $this->createStub(BlockCipher::class);

        $sut = new BlockCipherEncryptedCookie($keyManagerStub, $blockCipherStub);

        $cookieValue = $sut->encodeCookieValue($data);

        $this->assertEquals('', $cookieValue);
    }

    #[Test]
    public function it_decodes_a_string_into_session_data(): void
    {
        $data = [
            'session' => 'data',
        ];

        $keyMock = $this->createStub(Key::class);
        $keyMock->method('getId')->willReturn('1');

        $keyManagerStub = $this->createStub(KeyManagerInterface::class);
        $keyManagerStub->method('getDecryptionKey')->willReturn($keyMock);

        $cryptoStub = $this->createStub(BlockCipher::class);
        $cryptoStub->method('setKey')->willReturnSelf();
        $cryptoStub->method('decrypt')->willReturn('{"session":"data"}');

        $sut = new BlockCipherEncryptedCookie($keyManagerStub, $cryptoStub);

        $decoded = $sut->decodeCookieValue('1.ZW5jcnlwdGVkU3RyaW5n');

        $this->assertEquals($data, $decoded);
    }

    #[Test]
    public function it_throws_an_exception_when_key_id_not_matched_and_returns_new_session(): void
    {
        $keyManagerStub = $this->createStub(KeyManagerInterface::class);
        $keyManagerStub->method('getDecryptionKey')->willThrowException(new KeyNotFoundException());

        $blockCipherStub = $this->createStub(BlockCipher::class);

        $sut = new BlockCipherEncryptedCookie($keyManagerStub, $blockCipherStub);

        $decoded = $sut->decodeCookieValue('1.ZW5jcnlwdGVkU3RyaW5n');

        $this->assertEquals([], $decoded);
    }

    #[Test]
    public function it_decodes_an_empty_string_into_an_empty_array(): void
    {
        $data = [];

        $keyManagerStub  = $this->createStub(KeyManagerInterface::class);
        $blockCipherStub = $this->createStub(BlockCipher::class);

        $sut = new BlockCipherEncryptedCookie($keyManagerStub, $blockCipherStub);

        $sessionData = $sut->decodeCookieValue('');

        $this->assertEquals($data, $sessionData);
    }
}
