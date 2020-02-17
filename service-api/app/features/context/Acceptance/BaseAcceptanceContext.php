<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Acpr\Behat\Psr\Context\Psr11MinkAwareContext;
use Acpr\Behat\Psr\Context\RuntimeMinkContext;
use Aws\MockHandler as AwsMockHandler;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use JSHayes\FakeRequests\MockHandler;
use JSHayes\FakeRequests\RequestHandler;
use Psr\Container\ContainerInterface;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * Class BaseAcceptanceContext
 *
 * @package BehatTest\Context\Acceptanc
 *
 * @property RequestHandler $lastApiRequest
 */
class BaseAcceptanceContext extends RawMinkContext implements Psr11MinkAwareContext
{
    use RuntimeMinkContext;

    /**
     * @var ContainerInterface
     */
    public $container;

    /**
     * @var MockHandler
     */
    public $apiFixtures;

    /**
     * @var AwsMockHandler
     */
    public $awsFixtures;

    /**
     * @var MinkContext
     */
    public $ui;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;

        $this->apiFixtures = $container->get(MockHandler::class);
        $this->awsFixtures = $container->get(AwsMockHandler::class);
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->ui = $environment->getContext(MinkContext::class);
    }
}