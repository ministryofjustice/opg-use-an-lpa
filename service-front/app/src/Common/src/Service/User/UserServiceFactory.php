<?php

declare(strict_types=1);

namespace Common\Service\User;

use Common\Service\ApiClient\Client;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Authentication\UserInterface;

class UserServiceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new UserService(
            $container->get(Client::class),
            $container->get(UserInterface::class)
        );
    }
}
