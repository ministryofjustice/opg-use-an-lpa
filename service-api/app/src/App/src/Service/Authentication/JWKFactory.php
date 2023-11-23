<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use App\Service\Authentication\KeyPairManager\KeyPair;
use App\Service\Authentication\KeyPairManager\KeyPairManagerInterface;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory as KeyFactory;

/**
 * Generates a JWK when provided using a supplied KeyPairManager.
 *
 * The {@link KeyPair} can contain a private key but does not have to.
 */
class JWKFactory
{
    public function __invoke(KeyPairManagerInterface $keyPairManager): JWK
    {
        $keyPair = $keyPairManager->getKeyPair();

        return KeyFactory::createFromKey(
            $keyPair->hasPrivate() ? $keyPair->private->getString() : $keyPair->public,
            null,
            [
                //TODO UML-3056 These may need revisiting
                'alg' => $keyPairManager->getAlgorithm(),
                'use' => 'sig',
            ]
        );
    }
}
