<?php

declare(strict_types=1);

namespace Viewer\Service\Session\KeyManager;

use ParagonIE\Halite\Symmetric\EncryptionKey;

/**
 * Represents a single encryption key, and it ID.
 *
 * Class Key
 * @package App\Service\Session\KeyManager
 */
class Key
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var EncryptionKey
     */
    private $material;

    /**
     * Key constructor.
     *
     * @param int $id
     * @param EncryptionKey $material
     */
    public function __construct(int $id, EncryptionKey $material)
    {
        $this->id = $id;
        $this->material = $material;
    }

    /**
     * Return ihe key's ID
     *
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * Return the key material
     *
     * @return string
     */
    public function getKeyMaterial() : string
    {
        return $this->material->getRawKeyMaterial();
    }
}
