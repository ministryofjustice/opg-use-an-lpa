<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Acpr\Behat\Psr\Context\Psr11MinkAwareContext;
use Acpr\Behat\Psr\Context\RuntimeMinkContext;
use Aws\MockHandler as AwsMockHandler;
use Behat\MinkExtension\Context\RawMinkContext;
use JSHayes\FakeRequests\MockHandler;
use Psr\Container\ContainerInterface;
use Symfony\Component\BrowserKit\Client;
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

    protected function apiGet(string $url, array $headers): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'GET',
            $url,
            [],
            [],
            $this->createServerParams($headers)
        );
    }

    protected function apiPost(string $url, array $data, array $headers): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            $url,
            $data,
            [],
            $this->createServerParams($headers)
        );
    }

    protected function apiPut(string $url, array $data, array $headers): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'PUT',
            $url,
            $data,
            [],
            $this->createServerParams($headers)
        );
    }

    protected function apiPatch(string $url, array $data, array $headers): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'PATCH',
            $url,
            $data,
            [],
            $this->createServerParams($headers)
        );
    }

    private function createServerParams(array $headers): array
    {
        $serverParams = [];
        foreach ($headers as $headerName => $value) {
            $serverParams['HTTP_'.$headerName] = $value;
        }

        return $serverParams;
    }
}