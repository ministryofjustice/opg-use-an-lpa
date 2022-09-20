<?php

declare(strict_types=1);

namespace Common\Service\Session\KeyManager;

use ParagonIE\Halite\Symmetric\EncryptionKey;

/**
 * Represents a single encryption key, and it ID.
 */
class Key
{
    public function __construct(private string $id, private EncryptionKey $material)
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
     * Return the key material
     *
     * @return string
     */
    public function getKeyMaterial(): string
    {
        return $this->material->getRawKeyMaterial();
    }
}
