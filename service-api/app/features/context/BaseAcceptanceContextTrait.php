<?php

declare(strict_types=1);

namespace BehatTest\Context;

use Aws\DynamoDb\Marshaler;
use Aws\MockHandler as AwsMockHandler;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\MinkExtension\Context\MinkContext;
use BehatTest\Context\Acceptance\BaseAcceptanceContext;
use JSHayes\FakeRequests\MockHandler;
use JSHayes\FakeRequests\RequestHandler;

trait BaseAcceptanceContextTrait
{
    /**
     * @var BaseAcceptanceContext
     */
    protected $base;

    /**
     * @var MinkContext
     */
    protected $ui;

    /**
     * @var MockHandler
     */
    protected $apiFixtures;

    /**
     * @var AwsMockHandler
     */
    protected $awsFixtures;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->base = $environment->getContext(BaseAcceptanceContext::class);
        $this->ui = $this->base->ui; // MinkContext gathered in BaseUiContext
        $this->apiFixtures = $this->base->apiFixtures;
        $this->awsFixtures = $this->base->awsFixtures;
    }

    protected function getResponseAsJson(): array
    {
        assertJson($this->ui->getSession()->getPage()->getContent());
        return json_decode($this->ui->getSession()->getPage()->getContent(), true);
    }

    /**
     * Convert a key/value array to a correctly marshaled AwsResult structure.
     *
     * AwsResult data is in a special array format that tells you
     * what datatype things are. This function creates that data structure.
     *
     * @param array $input
     * @return array
     */
    protected function marshalAwsResultData(array $input): array
    {
        $marshaler = new Marshaler();

        return $marshaler->marshalItem($input);
    }

    protected function apiGet(string $url, ?array $headers = null): void
    {
        $this->ui->getSession()->getDriver()->getClient()->request(
            'GET',
            $url,
            [],
            [],
            $this->createServerParams($headers)
        );
    }

    protected function apiPost(string $url, array $data, ?array $headers = null): void
    {
        $this->ui->getSession()->getDriver()->getClient()->request(
            'POST',
            $url,
            $data,
            [],
            $this->createServerParams($headers)
        );
    }

    protected function apiPut(string $url, array $data, ?array $headers = null): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'PUT',
            $url,
            $data,
            [],
            $this->createServerParams($headers)
        );
    }

    protected function apiPatch(string $url, array $data, ?array $headers = null): void
    {
        $this->ui->getSession()->getDriver()->getClient()->request(
            'PATCH',
            $url,
            $data,
            [],
            $this->createServerParams($headers)
        );
    }

    protected function apiDelete(string $url, ?array $headers = null): void
    {
        $this->ui->getSession()->getDriver()->getClient()->request(
            'DELETE',
            $url,
            [],
            [],
            $this->createServerParams($headers)
        );
    }

    private function createServerParams(?array $headers = []): array
    {
        // this headerThief madness allows access to the private 'serverParameters' property of the BrowserKitDriver
        // the reason we need this is that by calling `request()` on the client directly we skip the merging of
        // headers that the driver does before calling the client, so we do that manually here with this magic.
        $driverHeaders = \Closure::bind(
            function (BrowserKitDriver $driver): array {
                return $driver->serverParameters;
            },
            null,
            $this->ui->getSession()->getDriver()
        );
        $presetHeaders = $driverHeaders($this->ui->getSession()->getDriver());

        $serverParams = [];
        foreach (($headers ?: []) as $headerName => $value) {
            $serverParams['HTTP_'.$headerName] = $value;
        }

        return array_merge($presetHeaders, $serverParams);
    }

    /**
     * Allows context steps to optionally store an api request mock as returned from calls to
     * `$this->apiFixtures->get|patch|post()`
     *
     * @param RequestHandler $request
     */
    public function setLastRequest(RequestHandler $request): void
    {
        $this->base->lastApiRequest = $request;
    }

    /**
     * Allow context steps to optionally fetch the last api request that was stored via a previous
     * call to {@link setLastRequest()}
     *
     * This function may not return the request you're expecting so ensure your feature test steps
     * set the value you want before use.
     *
     * @return RequestHandler
     */
    public function getLastRequest(): RequestHandler
    {
        return $this->base->lastApiRequest;
    }
}
