<?php

declare(strict_types=1);

namespace Common\Service\Session\KeyManager;

use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\Halite\Symmetric\SecretKey;

/**
 * Represents a single encryption key, and it ID.
 */
class Key
{
    public function __construct(private readonly string $id, private readonly EncryptionKey $material)
    {
    }

    /**
     * Return ihe key's ID
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
        return $this->material->getRawKeyMaterial();
    }
}
