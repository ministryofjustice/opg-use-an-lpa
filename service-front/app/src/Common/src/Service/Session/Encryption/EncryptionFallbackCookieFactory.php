<?php

declare(strict_types=1);

namespace Common\Service\Session\Encryption;

use Psr\Container\ContainerInterface;

class EncryptionFallbackCookieFactory
{
    public function __invoke(ContainerInterface $container): EncryptionFallbackCookie
    {
        return new EncryptionFallbackCookie(
            $container->get(HaliteEncryptedCookie::class),
        );
    }
}
