<?php

declare(strict_types=1);

namespace CommonTest\Service\Session\Encryption;

use Common\Service\Session\Encryption\KmsEncryptedCookie;
use Common\Service\Session\KeyManager\Key;
use Common\Service\Session\KeyManager\KeyManagerInterface;
use Common\Service\Session\KeyManager\KeyNotFoundException;
use Laminas\Crypt\BlockCipher;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class KmsEncryptedCookieTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated(): void
    {
        $keyManagerProphecy = $this->prophesize(KeyManagerInterface::class);
        $blockCipherProphecy = $this->prophesize(BlockCipher::class);

        $sut = new KmsEncryptedCookie($keyManagerProphecy->reveal(), $blockCipherProphecy->reveal());

        $this->assertInstanceOf(KmsEncryptedCookie::class, $sut);
    }

    /** @test */
    public function it_encodes_a_session_array(): void
    {
        $data = [
            'session' => 'data'
        ];

        $keyProphecy = $this->prophesize(Key::class);
        $keyProphecy->getKeyMaterial()->willReturn('encryptionKey');
        $keyProphecy->getId()->willReturn('1');

        $keyManagerProphecy = $this->prophesize(KeyManagerInterface::class);
        $keyManagerProphecy->getEncryptionKey()->willReturn($keyProphecy->reveal());

        $blockCipherProphecy = $this->prophesize(BlockCipher::class);
        $blockCipherProphecy->setKey('encryptionKey')->willReturn($blockCipherProphecy->reveal());
        $blockCipherProphecy->encrypt(Argument::type('string'))->willReturn('encryptedString');

        $sut = new KmsEncryptedCookie($keyManagerProphecy->reveal(), $blockCipherProphecy->reveal());

        $encoded = $sut->encodeCookieValue($data);

        $this->assertEquals('1.ZW5jcnlwdGVkU3RyaW5n', $encoded);
    }

    /** @test */
    public function it_encodes_an_empty_array_of_data_to_a_blank_string(): void
    {
        $data = [];

        $keyManagerProphecy = $this->prophesize(KeyManagerInterface::class);
        $blockCipherProphecy = $this->prophesize(BlockCipher::class);

        $sut = new KmsEncryptedCookie($keyManagerProphecy->reveal(), $blockCipherProphecy->reveal());

        $cookieValue = $sut->encodeCookieValue($data);

        $this->assertEquals('', $cookieValue);
    }

    /** @test */
    public function it_decodes_a_string_into_session_data(): void
    {
        $data = [
            'session' => 'data'
        ];

        $keyProphecy = $this->prophesize(Key::class);
        $keyProphecy->getKeyMaterial()->willReturn('encryptionKey');

        $keyManagerProphecy = $this->prophesize(KeyManagerInterface::class);
        $keyManagerProphecy->getDecryptionKey('1')->willReturn($keyProphecy->reveal());

        $blockCipherProphecy = $this->prophesize(BlockCipher::class);
        $blockCipherProphecy->setKey('encryptionKey')->willReturn($blockCipherProphecy->reveal());
        $blockCipherProphecy->decrypt(Argument::type('string'))->willReturn('{"session":"data"}');

        $sut = new KmsEncryptedCookie($keyManagerProphecy->reveal(), $blockCipherProphecy->reveal());

        $decoded = $sut->decodeCookieValue('1.ZW5jcnlwdGVkU3RyaW5n');

        $this->assertEquals($data, $decoded);
    }

    /** @test */
    public function it_throws_an_exception_when_key_id_not_matched_and_returns_new_session(): void
    {
        $keyProphecy = $this->prophesize(Key::class);

        $keyManagerProphecy = $this->prophesize(KeyManagerInterface::class);
        $keyManagerProphecy->getDecryptionKey('1')->willThrow(new KeyNotFoundException());

        $blockCipherProphecy = $this->prophesize(BlockCipher::class);

        $sut = new KmsEncryptedCookie($keyManagerProphecy->reveal(), $blockCipherProphecy->reveal());

        $decoded = $sut->decodeCookieValue('1.ZW5jcnlwdGVkU3RyaW5n');

        $this->assertEquals([], $decoded);
    }

    /** @test */
    public function it_decodes_an_empty_string_into_an_empty_array(): void
    {
        $data = [];

        $keyManagerProphecy = $this->prophesize(KeyManagerInterface::class);
        $blockCipherProphecy = $this->prophesize(BlockCipher::class);

        $sut = new KmsEncryptedCookie($keyManagerProphecy->reveal(), $blockCipherProphecy->reveal());

        $sessionData = $sut->decodeCookieValue('');

        $this->assertEquals($data, $sessionData);
    }
}
