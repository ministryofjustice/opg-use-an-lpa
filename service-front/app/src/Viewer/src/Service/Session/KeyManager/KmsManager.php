<?php

declare(strict_types=1);

namespace Viewer\Service\Session\KeyManager;

use Aws\Kms\KmsClient;
use Aws\Kms\Exception\KmsException;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\Halite\Alerts\InvalidKey;
use ParagonIE\HiddenString\HiddenString;

class KmsManager implements KeyManagerInterface
{

    /**
     * Time to cache encryption data key.
     */
    const ENCRYPTION_KEY_TTL = 60 * 60 * 1;

    /**
     * Time to cache decryption data keys.
     * These are held longer to allow for rotation crossover.
     */
    const DECRYPTION_KEY_TTL = 60 * 60 * 2;

    /**
     * Current Key name within the cache.
     */
    const CURRENT_ENCRYPTION_KEY = 'current_session_encryption_key';

    /**
     * @var string
     */
    private $kmsAlias;

    /**
     * @var KmsClient
     */
    private $kmsClient;

    /**
     * @var KeyCache
     */
    private $cache;

    /**
     * KmsManager constructor.
     *
     * @param KmsClient $kmsClient
     * @param KeyCache $cache
     * @param Config $config
     */
    public function __construct(KmsClient $kmsClient, KeyCache $cache, Config $config)
    {
        $this->kmsAlias = $config->getKeyAlias();
        $this->kmsClient = $kmsClient;
        $this->cache = $cache;
    }

    /**
     * Returns the key which should be used for encryption.
     *
     * @return Key
     * @throws InvalidKey
     */
    public function getEncryptionKey() : Key
    {
        $currentKey = $this->cache->get(static::CURRENT_ENCRYPTION_KEY);

        if ($currentKey !== false) {

            // If we found a current key

            $id = $currentKey['id'];
            $material = $currentKey['key_material'];

        } else {

            // Else we get a new key

            $newKey = $this->kmsClient->generateDataKey([
                'KeyId' => $this->kmsAlias,
                'KeySpec' => 'AES_256',
            ]);

            $id = base64_encode($newKey->get('CiphertextBlob'));

            $material = new HiddenString(
                (string)$newKey->get('Plaintext'),
                true,
                false
            );

            // Make this key the current key for encrypting
            $this->cache->store(static::CURRENT_ENCRYPTION_KEY, [
                'id' => $id,
                'key_material' => $material,
            ], self::ENCRYPTION_KEY_TTL);

            // And keep a copy for decrypting
            $this->cache->store($id, $material, self::DECRYPTION_KEY_TTL);
        }

        return new Key($id, new EncryptionKey($material));
    }

    /**
     * Returns the Key with the given $id, to be used for decryption.
     *
     * @param string $id
     * @return Key
     * @throws InvalidKey
     */
    public function getDecryptionKey(string $id) : Key
    {
        $material = $this->cache->get($id);

        if (!($material instanceof HiddenString)) {

            // Then we don't know the key. Pull it out of KMS.

            try {
                $key = $this->kmsClient->decrypt([
                    'CiphertextBlob' => base64_decode($id),
                ]);
            } catch (KmsException $e){
                if ($e->getAwsErrorCode() == 'InvalidCiphertextException') {
                    throw new KeyNotFoundException();
                }
                throw $e;
            }

            $material = new HiddenString(
                (string)$key->get('Plaintext'),
                true,
                false
            );

            // Keep a copy for decrypting
            $this->cache->store($id, $material, self::DECRYPTION_KEY_TTL);
        }

        return new Key($id, new EncryptionKey($material));
    }

}
