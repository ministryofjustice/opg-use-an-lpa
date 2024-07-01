<?php

declare(strict_types=1);

namespace App\Service\JWT;

use App\Service\Secrets\KeyPair;
use App\Service\Secrets\KeyPairManagerInterface;
use App\Service\Secrets\SecretManagerInterface;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory as KeyFactory;

/**
 * Generates a JWK when provided a supplied KeyPairManager or SecretManager
 *
 * The {@link KeyPair} can contain a private key but does not have to.
 */
class JWKFactory
{
    public function __invoke(KeyPairManagerInterface|SecretManagerInterface $manager): JWK
    {
        return match (true) {
            $manager instanceof KeyPairManagerInterface => $this->createFromKeyPair($manager),
            $manager instanceof SecretManagerInterface  => $this->createFromSecret($manager),
        };
    }

    private function createFromKeyPair(KeyPairManagerInterface $manager): JWK
    {
        $keyPair = $manager->getKeyPair();

        return KeyFactory::createFromKey(
            $keyPair->hasPrivate() ? $keyPair->private->getString() : $keyPair->public,
            null,
            [
                //TODO UML-3056 These may need revisiting
                'alg' => $manager->getAlgorithm(),
                'use' => 'sig',
            ]
        );
    }

    private function createFromSecret(SecretManagerInterface $manager): JWK
    {
        $secret = $manager->getSecret();

        return KeyFactory::createFromSecret(
            $secret->secret->getString(),
            [
                'alg' => $manager->getAlgorithm(),
                'use' => 'sig',
            ]
        );
    }
}
