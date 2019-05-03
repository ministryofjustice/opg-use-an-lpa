<?php

declare(strict_types=1);

namespace Viewer\Service\Session;

use Viewer\Service\Session\KeyManager\KeyNotFoundException;
use Viewer\Service\Session\KeyManager\KeyManagerInterface;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Zend\Crypt\BlockCipher;

/**
 * Provides encryption and decryption for session cookies.
 *
 * Class EncryptedCookie
 * @package App\Service\Session
 */
class EncryptedCookiePersistence extends CookiePersistence
{
    /**
     * @var KeyManagerInterface
     */
    private $keyManager;

    /**
     * EncryptedCookiePersistence constructor.
     * @param KeyManagerInterface $keyManager
     * @param Config $config
     */
    public function __construct(KeyManagerInterface $keyManager, Config $config)
    {
        parent::__construct($config);
        $this->keyManager = $keyManager;
    }

    /**
     * Returns the configured Block Cipher to be used within this class.
     *
     * @return BlockCipher
     */
    private function getBlockCipher() : BlockCipher
    {
        return BlockCipher::factory('openssl', [
            'algo' => 'aes',
            'mode' => 'gcm'
        ])->setBinaryOutput(true);
    }

    //---

    /**
     * Encrypts the session payload with the current (latest) key.
     *
     *  The result is <keyId>.<ciphertextr>
     *
     * @param array $data
     * @return string
     */
    protected function encodeCookieValue(array $data) : string
    {
        $plaintext = parent::encodeCookieValue($data);

        if (empty($plaintext)) {
            return '';
        }

        $key = $this->keyManager->getEncryptionKey();

        $ciphertext = $this->getBlockCipher()
            ->setKey($key->getKeyMaterial())
            ->encrypt($plaintext);

        return $key->getId() . '.' . Base64UrlSafe::encode($ciphertext);
    }

    /**
     * Decrypt the session value.
     *
     * @param string $data
     * @return array
     */
    protected function decodeCookieValue(string $data) : array
    {
        if (empty($data)) {
            return parent::decodeCookieValue($data);
        }

        list($keyId, $payload) = explode('.', $data, 2);

        try {

            $key = $this->keyManager->getDecryptionKey($keyId);

            $ciphertext = Base64UrlSafe::decode($payload);

            $plaintext = $this->getBlockCipher()
                ->setKey($key->getKeyMaterial())
                ->decrypt($ciphertext);

        } catch (KeyNotFoundException $e){
            # TODO: add logging

            // Restart the session
            $plaintext = '';
        }

        return parent::decodeCookieValue($plaintext);
    }

}
