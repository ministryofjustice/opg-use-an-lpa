<?php

declare(strict_types=1);

namespace Viewer\Service\Session\KeyManager;

interface KeyManagerInterface
{
    /**
     * Returns the key which should be used for encryption.
     *
     * @return Key
     */
    public function getEncryptionKey() : Key;

    /**
     * Returns the Key with the given $id, to be used for decryption.
     *
     * @param string $id
     * @return Key
     */
    public function getDecryptionKey(string $id) : Key;

}
