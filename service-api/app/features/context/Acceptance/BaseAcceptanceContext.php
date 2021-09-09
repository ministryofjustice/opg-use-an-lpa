<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Acpr\Behat\Psr\Context\Psr11MinkAwareContext;
use Acpr\Behat\Psr\Context\RuntimeMinkContext;
use Aws\MockHandler as AwsMockHandler;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use DI\Container;
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
 *
 * @property string userAccountId
 * @property string userAccountEmail
 * @property string userAccountPassword
 */
class BaseAcceptanceContext extends RawMinkContext implements Psr11MinkAwareContext
{
    use RuntimeMinkContext;

    /**
     * @var ContainerInterface|Container
     */
    public $container;
    public MockHandler $apiFixtures;
    public AwsMockHandler $awsFixtures;
    public MinkContext $ui;

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

    /**
     * @Given I am a user of the lpa application
     */
    public function iAmAUserOfTheLpaApplication()
    {
        $this->userAccountId = '123456789';
        $this->userAccountEmail = 'test@example.com';
        $this->userAccountPassword = 'pa33w0rd';
    }
}
