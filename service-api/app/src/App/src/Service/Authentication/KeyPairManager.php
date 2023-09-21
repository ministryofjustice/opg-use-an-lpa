<?php

declare(strict_types=1);

namespace App\Service\Authentication;

use Aws\SecretsManager\Exception\SecretsManagerException;
use Aws\SecretsManager\SecretsManagerClient;
use ParagonIE\HiddenString\HiddenString;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

class KeyPairManager
{
    use ProphecyTrait;


    const PUBLIC_KEY = 'gov_uk_onelogin_identity_public_key';
    const PRIVATE_KEY = 'gov_uk_onelogin_identity_private_key';

    public function __construct(private SecretsManagerClient $secretsManagerClient, private LoggerInterface $logger)
    {

    }

    public function getKeyPair(): KeyPair
    {
        try {
            $public = $this->secretsManagerClient->getSecretValue(['SecretId' => self::PUBLIC_KEY])->get('SecretString');
            $private = $this->secretsManagerClient->getSecretValue(['SecretId' => self::PRIVATE_KEY])->get('SecretString');
        } catch (SecretsManagerException $e) {
            throw $e;
        }
        $private = new HiddenString($private, true, false);
        return new KeyPair($public, $private);
    }

}