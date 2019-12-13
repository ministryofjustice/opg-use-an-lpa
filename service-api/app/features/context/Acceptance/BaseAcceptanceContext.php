<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Acpr\Behat\Psr\Context\Psr11MinkAwareContext;
use Acpr\Behat\Psr\Context\RuntimeMinkContext;
use Aws\MockHandler as AwsMockHandler;
use Behat\MinkExtension\Context\RawMinkContext;
use JSHayes\FakeRequests\MockHandler;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

abstract class BaseAcceptanceContext extends RawMinkContext implements Psr11MinkAwareContext
{
    use RuntimeMinkContext;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Application
     */
    protected $application;

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
        $this->application = $this->container->get(Application::class);

        $this->apiFixtures = $container->get(MockHandler::class);
        $this->awsFixtures = $container->get(AwsMockHandler::class);
    }

    protected function getResponseAsJson(): array
    {
        assertJson($this->getSession()->getPage()->getContent());
        return json_decode($this->getSession()->getPage()->getContent(), true);
    }

    protected function apiGet(string $url): void
    {
        $this->getSession()->getDriver()->getClient()->request('GET', $url);
    }

    protected function apiPost(string $url, array $data): void
    {
        $this->getSession()->getDriver()->getClient()->request('POST', $url, $data);
    }

    protected function apiPatch(string $url, array $data): void
    {
        $this->getSession()->getDriver()->getClient()->request('PATCH', $url, $data);
    }
}