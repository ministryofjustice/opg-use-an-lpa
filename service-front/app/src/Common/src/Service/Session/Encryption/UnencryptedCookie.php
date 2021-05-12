<?php

declare(strict_types=1);

namespace Common\Service\Session\Encryption;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Psr\Log\LoggerInterface;

class UnencryptedCookie implements EncryptInterface
{
    public function __construct(LoggerInterface $logger)
    {
        $logger->critical(
            '----- WARNING ----- Service is currently operating with unencrypted cookies ----- WARNING -----'
        );
    }

    /**
     * @inheritDoc
     */
    public function encodeCookieValue(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $plaintext = json_encode($data);

        return Base64UrlSafe::encode($plaintext);
    }

    /**
     * @inheritDoc
     */
    public function decodeCookieValue(string $data): array
    {
        if (empty($data)) {
            return [];
        }

        $plaintext = Base64UrlSafe::decode($data);

        return json_decode($plaintext, true);
    }
}
