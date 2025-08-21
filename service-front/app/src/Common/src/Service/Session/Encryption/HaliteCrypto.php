<?php

declare(strict_types=1);

namespace Common\Service\Session\Encryption;

use Common\Service\Session\KeyManager\Key;
use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Alerts\InvalidDigestLength;
use ParagonIE\Halite\Alerts\InvalidMessage;
use ParagonIE\Halite\Alerts\InvalidSignature;
use ParagonIE\Halite\Alerts\InvalidType;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\HiddenString\HiddenString;
use SodiumException;

/**
 * Wrapper class for static Halite Crypto functions to allow better testability of our code.
 *
 * The downside being that this class is basically untestable without actually running cryptography operations
 *
 * @codeCoverageIgnore
 */
readonly class HaliteCrypto
{
    /**
     * @throws InvalidType
     * @throws InvalidMessage
     * @throws InvalidDigestLength
     * @throws CannotPerformOperation
     * @throws SodiumException
     */
    public function encrypt(string $plaintext, Key $key): string
    {
        return Crypto::encrypt(new HiddenString($plaintext), $key->getKey());
    }

    /**
     * @throws InvalidType
     * @throws InvalidSignature
     * @throws InvalidMessage
     * @throws InvalidDigestLength
     * @throws CannotPerformOperation
     * @throws SodiumException
     */
    public function decrypt(string $ciphertext, Key $key): string
    {
        return Crypto::decrypt($ciphertext, $key->getKey())->getString();
    }
}
