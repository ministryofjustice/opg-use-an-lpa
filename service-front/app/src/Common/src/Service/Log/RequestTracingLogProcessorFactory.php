<?php

declare(strict_types=1);

namespace Common\Service\Log;

use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use WShafer\PSR11MonoLog\ContainerAwareInterface;
use WShafer\PSR11MonoLog\FactoryInterface;

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