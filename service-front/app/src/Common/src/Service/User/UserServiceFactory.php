<?php

declare(strict_types=1);

namespace Common\Service\User;

use Common\Service\ApiClient\Client;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Mezzio\Authentication\UserInterface;

class UserServiceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new UserService(
            $container->get(Client::class),
            $container->get(UserInterface::class),
            $container->get(LoggerInterface::class)
        );
    }
}
