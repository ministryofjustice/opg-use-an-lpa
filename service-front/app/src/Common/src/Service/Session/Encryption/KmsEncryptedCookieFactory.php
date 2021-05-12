<?php

declare(strict_types=1);

namespace Common\Service\Session\Encryption;

use Common\Service\Session\KeyManager\KeyManagerInterface;
use Laminas\Crypt\BlockCipher;
use Psr\Container\ContainerInterface;

class KmsEncryptedCookieFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new KmsEncryptedCookie(
            $container->get(KeyManagerInterface::class),
            BlockCipher::factory(
                'openssl',
                [
                    'algo' => 'aes',
                    'mode' => 'gcm'
                ]
            )->setBinaryOutput(true)
        );
    }
}
