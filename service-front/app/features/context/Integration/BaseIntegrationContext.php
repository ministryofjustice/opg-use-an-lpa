<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Acpr\Behat\Psr\Context\Psr11AwareContext;
use Behat\Behat\Context\Context;
use DI\Container;
use Psr\Container\ContainerInterface;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

abstract class BaseIntegrationContext implements Context, Psr11AwareContext
{
    /**
     * @var ContainerInterface|Container
     */
    protected $container;

    /**
     * @inheritDoc
     */
    final public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;

        $this->prepareContext();
    }

    /**
     * Called after the PSR11 Container has been set into this Context
     */
    abstract protected function prepareContext(): void;
}