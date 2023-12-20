<?php

declare(strict_types=1);

namespace Common\Service\OneLogin;

use Common\Service\ApiClient\Client;
use Mezzio\Authentication\UserInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class OneLoginServiceFactory
{
    public function __invoke(ContainerInterface $container): OneLoginService
    {
        return new OneLoginService(
            $container->get(Client::class),
            $container->get(UserInterface::class),
            $container->get(LoggerInterface::class)
        );
    }
}