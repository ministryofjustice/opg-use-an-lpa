<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Acpr\Behat\Psr\Context\Psr11MinkAwareContext;
use Acpr\Behat\Psr\Context\RuntimeMinkContext;
use Aws\MockHandler as AwsMockHandler;
use Behat\MinkExtension\Context\MinkContext;
use GuzzleHttp\Handler\MockHandler;
use Psr\Container\ContainerInterface;

abstract class BaseUIContext extends MinkContext implements Psr11MinkAwareContext
{
    use RuntimeMinkContext;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var MockHandler
     */
    protected $apiFixtures;

    /**
     * @var AwsMockHandler
     */
    protected $awsFixtures;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;

        $this->apiFixtures = $container->get(MockHandler::class);
        $this->awsFixtures = $container->get(AwsMockHandler::class);
    }
}