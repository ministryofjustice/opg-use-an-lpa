<?php

declare(strict_types=1);

namespace App\Service\SystemMessage;

use Aws\Ssm\SsmClient;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SystemMessageFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): SystemMessage
    {
        $config = $container->get('config');

        $environmentName = $config['environment_name'];

        $prefix = '/system-message/' . ($environmentName !== '' ? $environmentName . '/' : '');

        return new SystemMessage($container->get(SsmClient::class), $prefix);
    }
}
