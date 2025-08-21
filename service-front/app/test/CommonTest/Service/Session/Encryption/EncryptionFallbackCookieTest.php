<?php

declare(strict_types=1);

namespace CommonTest\Service\Session\Encryption;

use Common\Service\Session\Encryption\EncryptInterface;
use Common\Service\Session\Encryption\EncryptionFallbackCookie;
use PHPUnit\Framework\Attributes\{CoversClass, DoesNotPerformAssertions, Test};
use PHPUnit\Framework\TestCase;

#[CoversClass(EncryptionFallbackCookie::class)]
class EncryptionFallbackCookieTest extends TestCase
{
    #[Test]
    #[DoesNotPerformAssertions]
    public function it_can_be_instantiated_with_single_implementation(): void
    {
        new EncryptionFallbackCookie($this->createStub(EncryptInterface::class));
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function it_can_be_instantiated_with_multiple_implementations(): void
    {
        new EncryptionFallbackCookie(
            $this->createStub(EncryptInterface::class),
            $this->createStub(EncryptInterface::class),
            $this->createStub(EncryptInterface::class)
        );
    }

    #[Test]
    public function it_encrypts_with_the_primary_implementation(): void
    {
        $mockEncrypt = $this->createMock(EncryptInterface::class);
        $mockEncrypt
            ->expects($this->once())
            ->method('encodeCookieValue')
            ->willReturn('encryptedCookieValue');

        $sut = new EncryptionFallbackCookie(
            $mockEncrypt,
            $this->createStub(EncryptInterface::class),
            $this->createStub(EncryptInterface::class)
        );

        $result = $sut->encodeCookieValue(['sessionData']);
        $this->assertEquals('encryptedCookieValue', $result);
    }

    #[Test]
    public function it_decrypts_in_turn_until_success(): void
    {
        $mockPrimaryEncrypt = $this->createMock(EncryptInterface::class);
        $mockPrimaryEncrypt
            ->expects($this->once())
            ->method('decodeCookieValue')
            ->willReturn([]);

        $mockSecondaryEncrypt = $this->createMock(EncryptInterface::class);
        $mockSecondaryEncrypt
            ->expects($this->once())
            ->method('decodeCookieValue')
            ->willReturn(['sessionData']);

        $sut = new EncryptionFallbackCookie(
            $mockPrimaryEncrypt,
            $this->createStub(EncryptInterface::class),
            $mockSecondaryEncrypt,
        );

        $result = $sut->decodeCookieValue('encryptedCookieValue');
        $this->assertEquals(['sessionData'], $result);
    }
}
