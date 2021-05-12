<?php

declare(strict_types=1);

namespace Common\Service\Session\Encryption;

interface EncryptInterface
{
    /**
     * Encrypts the session payload
     *
     * @param array $data Key/value components of the session
     * @return string An encrypted string ready to be used as a raw cookie value
     */
    public function encodeCookieValue(array $data): string;

    /**
     * Decrypt the session value.
     *
     * @param string $data The raw cookie value
     * @return array Key/value components of the session
     */
    public function decodeCookieValue(string $data): array;
}
