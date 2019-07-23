<?php

declare(strict_types=1);

namespace Common\Service\Log;

use Psr\Container\ContainerInterface;

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
            new LogStderrListener
        );
        return $errorHandler;
    }
}
