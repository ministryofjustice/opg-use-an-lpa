<?php

declare(strict_types=1);

namespace Common\Service\Session\Encryption;

use ParagonIE\ConstantTime\Base64UrlSafe;

class UnencryptedCookie implements EncryptInterface
{
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
