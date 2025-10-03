<?php

declare(strict_types=1);

namespace Common\Service\Session\KeyManager;

use ParagonIE\Halite\Symmetric\EncryptionKey;

/**
 * Represents a single encryption key, and it ID.
 */
readonly class Key
{
    public function __construct(private string $id, private EncryptionKey $material)
    {
    }

    /**
     * Return the key's ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Retrieve the underlying Halite backed encryption key
     *
     * @return EncryptionKey
     */
    public function getKey(): EncryptionKey
    {
        return $this->material;
    }

    /**
     * Return the key material
     *
     * @return string
     */
    public function getKeyMaterial(): string
    {
        return $this->getKey()->getRawKeyMaterial();
    }
}
