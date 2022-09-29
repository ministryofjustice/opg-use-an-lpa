<?php

declare(strict_types=1);

namespace Common\Service\Session\Encryption;

use Common\Service\Session\KeyManager\KeyManagerInterface;
use Common\Service\Session\KeyManager\KeyNotFoundException;
use Laminas\Crypt\BlockCipher;
use ParagonIE\ConstantTime\Base64UrlSafe;

class KmsEncryptedCookie implements EncryptInterface
{
    public function __construct(private KeyManagerInterface $keyManager, private BlockCipher $blockCipher)
    {
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

        $key = $this->keyManager->getEncryptionKey();

        $ciphertext = $this->blockCipher
            ->setKey($key->getKeyMaterial())
            ->encrypt($plaintext);

        return $key->getId() . '.' . Base64UrlSafe::encode($ciphertext);
    }

    /**
     * @inheritDoc
     */
    public function decodeCookieValue(string $data): array
    {
        if (empty($data)) {
            return [];
        }

        // unquote the value if necessary
        $data = preg_replace('/\\\\(.)|"/', '$1', $data);

        // Separate out the key ID and the data
        [$keyId, $payload] = explode('.', trim($data, '"'), 2);

        try {
            $key = $this->keyManager->getDecryptionKey($keyId);

            $ciphertext = Base64UrlSafe::decode($payload);

            $plaintext = $this->blockCipher
                ->setKey($key->getKeyMaterial())
                ->decrypt($ciphertext);

            return json_decode($plaintext, true);
        } catch (KeyNotFoundException) {
            # TODO: add logging
        }

        // Something went wrong. Restart the session.
        return [];
    }
}
