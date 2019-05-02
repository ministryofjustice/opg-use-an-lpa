<?php

declare(strict_types=1);

namespace Viewer\Service\Session\KeyManager;

use Aws\SecretsManager\SecretsManagerClient;
use RuntimeException;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;

/**
 * Class Manager
 * @package App\Service\Session\KeyManager
 */
class Manager implements KeyManagerInterface
{
    /**
     * Name of the cache key under which session data is stored.
     */
    const CACHE_SESSION_KEY = 'session_keys';

    /**
     * Name of the cache key under which we stored the time we last updated the secrets.
     */
    const CACHE_SESSION_UPDATED_KEY = 'session_keys_updated_at';

    /**
     * @var string
     */
    private $name;

    /**
     * @var KeyCache
     */
    private $cache;

    /**
     * @var SecretsManagerClient
     */
    private $secretsManagerClient;

    /**
     * Manager constructor.
     * @param string $name
     * @param SecretsManagerClient $secretsManagerClient
     * @param KeyCache $cache
     */
    public function __construct(string $name, SecretsManagerClient $secretsManagerClient, KeyCache $cache)
    {
        $this->name = $name;
        $this->secretsManagerClient = $secretsManagerClient;
        $this->cache = $cache;
    }

    /**
     * Returns the current (latest) session key
     *
     * @return Key
     * @throws \ParagonIE\Halite\Alerts\InvalidKey
     */
    public function getEncryptionKey() : Key
    {
        return $this->getKeyId();
    }

    public function getDecryptionKey(string $id) : Key
    {
        return $this->getKeyId($id);
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
    private function getKeyId(?string $id = null) : Key
    {
        // Gets the keys out of the cache
        $keys = $this->cache->get(static::CACHE_SESSION_KEY);

        // If we didn't find any, retrieve them from Secrets Manager
        if (!is_array($keys) || count($keys) != 2) {
            $keys = $this->updateSecrets(false);
        }

        // If we still don't have them...
        if (!is_array($keys) || count($keys) != 2) {
            throw new RuntimeException('Unable to load session keys');
        }

        //---

        // If no specific key was asked for...
        if (is_null($id)) {

            // Return the latest (last) key in the array
            $id = array_key_last($keys);

        } else {

            // Else return the specific key.

            // If we don't have the key being requested then attempt to request it
            if (!isset($keys[$id])) {
                $keys = $this->updateSecrets(true);
            }

            if (!isset($keys[$id])) {
                throw new KeyNotFoundException('Unable to find key for ID: ' . $id);
            }
        }

        return new Key((string)$id, new EncryptionKey($keys[$id]));
    }

    /**
     * Retrieve the latest set of keys from Secrets Manager
     *
     * @param bool $throttle
     * @return array
     */
    private function updateSecrets(bool $throttle) : array
    {
        // We we should apply throttling to the update...
        if ($throttle) {

            $lastUpdated = $this->cache->get(static::CACHE_SESSION_UPDATED_KEY);

            // Only allow a refresh attempt every few seconds.
            if (is_int($lastUpdated) && time() < ($lastUpdated + 10)) {
                throw new ThrottledRefreshException("Too many attempts to refresh the session keys");
            }
        }

        //---

        $result = $this->secretsManagerClient->getSecretValue([
            'SecretId' => $this->name
        ]);

        if (!$result->hasKey('SecretString')) {
            throw new RuntimeException('Invalid response from Secrets Manager; missing SecretString');
        }

        //---

        $secrets = json_decode($result->get('SecretString'), true);

        if (!is_array($secrets)){
            throw new RuntimeException('Invalid response from Secrets Manager; invalid JSON');
        }

        // Map the returned value to HiddenStrings
        $secrets = array_map(function ($v){
            return new HiddenString(
                sodium_hex2bin($v),
                true,
                false
            );
        }, $secrets);

        // Ensure keys are stored oldest to newest.
        ksort($secrets, SORT_NUMERIC);

        //---

        // Cache the keys
        $this->cache->store(static::CACHE_SESSION_KEY, $secrets, 900);

        // Record the time we cached them
        $this->cache->store(static::CACHE_SESSION_UPDATED_KEY, time());

        return $secrets;
    }
}
