<?php

declare(strict_types=1);

namespace Common\Handler\Factory;

use Common\Handler\SessionCheckHandler;
use Mezzio\Exception\RuntimeException;
use Psr\Container\ContainerInterface;

class SessionCheckHandlerFactory
{
    public function __invoke(ContainerInterface $container): SessionCheckHandler
    {
        //
        $config = $container->get('config');

        if (!isset($config['session']['expires'])) {
            throw new RuntimeException('Missing session expiry value');
        }

        return new SessionCheckHandler($config['session']['expires']);
    }
}
