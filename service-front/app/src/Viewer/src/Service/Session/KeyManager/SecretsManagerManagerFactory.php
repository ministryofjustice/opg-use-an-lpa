<?php

declare(strict_types=1);

namespace Viewer\Service\Session\KeyManager;

use Aws\SecretsManager\SecretsManagerClient;
use Psr\Container\ContainerInterface;

/**
 * Class ManagerFactory
 * @package App
 */
class SecretsManagerManagerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (empty($config['session']['key']['name'])) {
            throw new \RuntimeException('Secret name is missing');
        }

        return new SecretsManagerManager(
            $config['session']['key']['name'],
            $container->get(SecretsManagerClient::class),
            $container->get(KeyCache::class)
        );
    }
}
