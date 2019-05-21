<?php

declare(strict_types=1);

namespace Viewer\Service\Session\KeyManager;

use Aws\Kms\KmsClient;
use Psr\Container\ContainerInterface;
use RuntimeException;

class KmsManagerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['session']['key']) || empty($config['session']['key']['alias'])) {
            throw new RuntimeException('KMS CMK alias is missing');
        }

        return new KmsManager(
            $container->get(KmsClient::class),
            $container->get(KeyCache::class),
            $config['session']['key']['alias']
        );
    }
}
