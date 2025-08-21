<?php

declare(strict_types=1);

namespace CommonTest\Service\Session\Encryption;

use Common\Service\Session\Encryption\EncryptInterface;
use Common\Service\Session\Encryption\EncryptionFallbackCookieFactory;
use Common\Service\Session\Encryption\HaliteEncryptedCookie;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(EncryptionFallbackCookieFactory::class)]
class EncryptionFallbackCookieFactoryTest extends TestCase
{
    #[Test]
    public function it_creates_a_halite_primary_fallback_cookie(): void
    {
        $matcher       = $this->exactly(1);
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock
            ->expects($matcher)
            ->method('get')
            ->willReturnCallback(function (string $class) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals(HaliteEncryptedCookie::class, $class),
                };

                return $this->createStub(EncryptInterface::class);
            });

        $sut = new EncryptionFallbackCookieFactory();

        ($sut)($containerMock);
    }
}
