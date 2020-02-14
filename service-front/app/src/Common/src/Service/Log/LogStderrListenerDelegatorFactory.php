<?php

declare(strict_types=1);

namespace Common\Service\Log;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LogStderrListenerDelegatorFactory
{
    /**
     * @param ContainerInterface $container
     * @param string $name
     * @param callable $callback
     * @param array $options
     * @return LogStderrListener
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        $errorHandler = $callback();
        $errorHandler->attachListener(
            new LogStderrListener($container->get(LoggerInterface::class))
        );
        return $errorHandler;
    }
}
