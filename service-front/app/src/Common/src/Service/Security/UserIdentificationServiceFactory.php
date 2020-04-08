<?php

declare(strict_types=1);

namespace Common\Service\Security;

use Psr\Container\ContainerInterface;
use RuntimeException;

class UserIdentificationServiceFactory
{
    public function __invoke(ContainerInterface $container): UserIdentificationService
    {
        $config = $container->get('config');

        if (!isset($config['security'])) {
            throw new RuntimeException('Missing security configuration');
        }

        if (!isset($config['security']['uid_hash_salt'])) {
            throw new RuntimeException('Missing hashing salt for user identification');
        }

        return new UserIdentificationService($config['security']['uid_hash_salt']);
    }
}