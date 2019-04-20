<?php

declare(strict_types=1);

namespace Viewer\Service\Session\KeyManager;

use Aws\Kms\KmsClient;
use Aws\Kms\Exception\KmsException;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;

class KmsManager
{

    const CURRENT_KEY = 'current_session_key';

    private $kmsAlias;

    private $kmsClient;

    private $cache;

    public function __construct(KmsClient $kmsClient, KeyCache $cache)
    {
        $this->kmsAlias = 'alias/viewer-sessions-key';
        $this->kmsClient = $kmsClient;
        $this->cache = $cache;
    }

    /**
     * Returns the current (latest) session key
     *
     * @return Key
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     */
    public function getCurrentKey() : Key
    {
        return $this->getKeyId();
    }

    /**
     * Returns the specified session key.
     *
     * If $id is null, the latest (last) session key is returned.
     *
     * @param null|string $id
     * @return Key
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     */
    public function getKeyId(?string $id = null) : Key
    {
        // If no specific key was asked for...
        if (is_null($id)) {

            $currentKey = $this->cache->get(static::CURRENT_KEY);

            if ($currentKey !== false) {

                $id = $currentKey['id'];
                $material = $currentKey['key_material'];

            } else {

                $newKey = $this->kmsClient->generateDataKey([
                    'KeyId' => $this->kmsAlias,
                    'KeySpec' => 'AES_256',
                ]);

                $id = base64_encode($newKey->get('CiphertextBlob'));

                $material = new HiddenString(
                    $newKey->get('Plaintext'),
                    true,
                    false
                );

                // Make this key the current key for encrypting
                $this->cache->store(static::CURRENT_KEY, [
                    'id' => $id,
                    'key_material' => $material,
                ], 60 * 60);

                // And keep a copy for decrypting
                $this->cache->store($id, $material, 70 * 60);
            }

        } else {

            $material = $this->cache->get($id);

            if ($material === false) {
                // Then we don't know the key

                // Pull it out of KMS
                try {
                    $key = $this->kmsClient->decrypt([
                        'CiphertextBlob' => base64_decode($id),
                    ]);
                } catch ( KmsException $e){
                    if ($e->getAwsErrorCode() == 'InvalidCiphertextException') {
                        throw new KeyNotFoundException();
                    }
                    throw $e;
                }

                $material = new HiddenString(
                    $key->get('Plaintext'),
                    true,
                    false
                );

                // Keep a copy for decrypting
                $this->cache->store($id, $material, 70 * 60);
            }

        }

        return new Key($id, new EncryptionKey($material));
    }

}
