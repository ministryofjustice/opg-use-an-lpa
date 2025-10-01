<?php

declare(strict_types=1);

namespace CommonTest\Service\Session\Encryption;

use Common\Exception\SessionEncryptionFailureException;
use Common\Service\Session\Encryption\HaliteCrypto;
use Common\Service\Session\Encryption\HaliteEncryptedCookie;
use Common\Service\Session\KeyManager\Key;
use Common\Service\Session\KeyManager\KeyManagerInterface;
use Common\Service\Session\KeyManager\KeyNotFoundException;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(HaliteEncryptedCookie::class)]
class HaliteEncryptedCookieTest extends TestCase
{
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

        $cryptoStub = $this->createStub(HaliteCrypto::class);
        $cryptoStub->method('encrypt')->willReturn('encryptedString');

        $loggerStub = $this->createStub(LoggerInterface::class);

        $sut = new HaliteEncryptedCookie($keyManagerStub, $cryptoStub, $loggerStub);

        $encoded = $sut->encodeCookieValue($data);
        $this->assertEquals('1.encryptedString', $encoded);
    }

    #[Test]
    public function it_encodes_an_empty_array_of_data_to_a_blank_string(): void
    {
        $data = [];

        $keyManagerStub = $this->createStub(KeyManagerInterface::class);
        $cryptoStub     = $this->createStub(HaliteCrypto::class);
        $loggerStub     = $this->createStub(LoggerInterface::class);

        $sut = new HaliteEncryptedCookie($keyManagerStub, $cryptoStub, $loggerStub);

        $encoded = $sut->encodeCookieValue($data);
        $this->assertEquals('', $encoded);
    }

    #[Test]
    public function it_decodes_a_string_into_session_data(): void
    {
        $payload = '1.encryptedString';

        $keyStub = $this->createStub(Key::class);

        $keyManagerStub = $this->createStub(KeyManagerInterface::class);
        $keyManagerStub->method('getDecryptionKey')->willReturn($keyStub);

        $cryptoMock = $this->createMock(HaliteCrypto::class);
        $cryptoMock
            ->method('decrypt')
            ->with('encryptedString', $keyStub)
            ->willReturn('{"session":"data"}');

        $loggerStub = $this->createStub(LoggerInterface::class);

        $sut = new HaliteEncryptedCookie($keyManagerStub, $cryptoMock, $loggerStub);

        $data = $sut->decodeCookieValue($payload);
        $this->assertEquals('data', $data['session']);
    }

    #[Test]
    public function it_throws_an_exception_when_key_id_not_matched_and_returns_new_session(): void
    {
        $payload = '1.ZW5jcnlwdGVkU3RyaW5n';

        $keyManagerStub = $this->createStub(KeyManagerInterface::class);
        $keyManagerStub->method('getDecryptionKey')->willThrowException(new KeyNotFoundException());

        $cryptoMock = $this->createMock(HaliteCrypto::class);
        $loggerStub = $this->createStub(LoggerInterface::class);

        $sut = new HaliteEncryptedCookie($keyManagerStub, $cryptoMock, $loggerStub);

        $data = $sut->decodeCookieValue($payload);
        $this->assertEquals([], $data);
    }

    #[Test]
    public function it_throws_an_exception_when_decryption_fails_for_some_reason(): void
    {
        $payload = '1.ZW5jcnlwdGVkU3RyaW5n';

        $keyStub = $this->createStub(Key::class);

        $keyManagerStub = $this->createStub(KeyManagerInterface::class);
        $keyManagerStub->method('getDecryptionKey')->willReturn($keyStub);

        $cryptoMock = $this->createMock(HaliteCrypto::class);
        $cryptoMock
            ->method('decrypt')
            ->with('encryptedString', $keyStub)
            ->willThrowException(new Exception());

        $loggerStub = $this->createStub(LoggerInterface::class);

        $sut = new HaliteEncryptedCookie($keyManagerStub, $cryptoMock, $loggerStub);

        $data = $sut->decodeCookieValue($payload);
        $this->assertEquals([], $data);
    }

    #[Test]
    public function it_throws_an_exception_when_encryption_fails_for_some_reason(): void
    {
        $payload = ['some_data'];

        $keyManagerStub = $this->createStub(KeyManagerInterface::class);
        $keyManagerStub->method('getEncryptionKey')->willThrowException(new Exception());

        $cryptoMock = $this->createMock(HaliteCrypto::class);
        $loggerStub = $this->createStub(LoggerInterface::class);

        $sut = new HaliteEncryptedCookie($keyManagerStub, $cryptoMock, $loggerStub);

        $this->expectException(SessionEncryptionFailureException::class);
        $sut->encodeCookieValue($payload);
    }

    #[Test]
    public function it_decodes_an_empty_string_into_an_empty_array(): void
    {
        $data = '';

        $keyManagerStub = $this->createStub(KeyManagerInterface::class);
        $cryptoStub     = $this->createStub(HaliteCrypto::class);
        $loggerStub     = $this->createStub(LoggerInterface::class);

        $sut = new HaliteEncryptedCookie($keyManagerStub, $cryptoStub, $loggerStub);

        $encoded = $sut->decodeCookieValue($data);
        $this->assertEquals([], $encoded);
    }
}
