<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use Jose\Component\KeyManagement\JWKFactory as KeyFactory;
use Jose\Component\Core\JWK;

class JWKFactory
{
    public function __construct(private KeyPairManager $keyPairManager)
    {
    }

    public function __invoke(): JWK
    {
        return KeyFactory::createFromKey(
            $this->keyPairManager->getKeyPair()->private->getString(),
            null,
            [
                //TODO UML-3056 These may need revisiting
                'alg' => 'RS256',
                'use' => 'sig',
            ]
        );
    }
}
