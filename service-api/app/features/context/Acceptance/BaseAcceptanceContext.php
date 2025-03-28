<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Acpr\Behat\Psr\Context\Psr11MinkAwareContext;
use Acpr\Behat\Psr\Context\RuntimeMinkContext;
use Aws\MockHandler as AwsMockHandler;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\BeforeScenario;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Step\Given;
use DI\Container;
use GuzzleHttp\Handler\MockHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;

class BaseAcceptanceContext extends RawMinkContext implements Psr11MinkAwareContext
{
    use RuntimeMinkContext;

    public ContainerInterface|Container $container;
    public MockHandler $apiFixtures;
    public AwsMockHandler $awsFixtures;
    public MinkContext $ui;

    public string $userAccountId;
    public string $userAccountEmail;
    public string $userAccountPassword;
    public RequestInterface $lastApiRequest;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;

        $this->apiFixtures = $container->get(MockHandler::class);
        $this->awsFixtures = $container->get(AwsMockHandler::class);
    }

    #[BeforeScenario]
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->ui    = $environment->getContext(MinkContext::class);
    }

    #[Given('I am a user of the lpa application')]
    public function iAmAUserOfTheLpaApplication(): void
    {
        $this->userAccountId       = '123456789';
        $this->userAccountEmail    = 'test@example.com';
        $this->userAccountPassword = 'pa33w0rd';
    }
}
