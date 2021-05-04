<?php

declare(strict_types=1);

namespace Common\Service\Log;

use Blazon\PSR11MonoLog\ContainerAwareInterface;
use Blazon\PSR11MonoLog\FactoryInterface;
use Psr\Container\ContainerInterface;

class RequestTracingLogProcessorFactory implements FactoryInterface, ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __invoke(array $options): RequestTracingLogProcessor
    {
        return new RequestTracingLogProcessor($this->getContainer());
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
