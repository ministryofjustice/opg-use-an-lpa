<?php

declare(strict_types=1);

namespace Common\Service\SystemMessage;

use Common\Service\ApiClient\Client;
use Psr\Container\ContainerInterface;

class SystemMessageServiceFactory
{
    public function __invoke(ContainerInterface $container): SystemMessageService
    {
        return new SystemMessageService(
            $container->get(Client::class),
        );
    }
}
