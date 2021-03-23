<?php

declare(strict_types=1);

namespace Common\Service\Session\Encryption;

use Common\Service\Session\KeyManager\KeyManagerInterface;
use Common\Service\Session\KeyManager\KeyNotFoundException;
use Laminas\Crypt\BlockCipher;
use ParagonIE\ConstantTime\Base64UrlSafe;

class KmsEncryptedCookie implements EncryptInterface
{
    /** @var KeyManagerInterface */
    private KeyManagerInterface $keyManager;

    public function __construct(KeyManagerInterface $keyManager)
    {
        $this->keyManager = $keyManager;
    }

    /**
     * Returns the configured Block Cipher to be used within this class.
     *
     * @return BlockCipher
     */
    private function getBlockCipher(): BlockCipher
    {
        return BlockCipher::factory('openssl', [
            'algo' => 'aes',
            'mode' => 'gcm'
        ])->setBinaryOutput(true);
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

        $ciphertext = $this->getBlockCipher()
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

        // Separate out the key ID and the data
        [$keyId, $payload] = explode('.', $data, 2);

        try {
            $key = $this->keyManager->getDecryptionKey($keyId);

            $ciphertext = Base64UrlSafe::decode($payload);

            $plaintext = $this->getBlockCipher()
                ->setKey($key->getKeyMaterial())
                ->decrypt($ciphertext);

            return json_decode($plaintext, true);
        } catch (KeyNotFoundException $e) {
            # TODO: add logging
        }

        // Something went wrong. Restart the session.
        return [];
    }
}
