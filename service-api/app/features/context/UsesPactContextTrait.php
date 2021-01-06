<?php

declare(strict_types=1);

namespace BehatTest\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use PhpPact\Exception\ConnectionException;
use SmartGamma\Behat\PactExtension\Context\PactContext;
use SmartGamma\Behat\PactExtension\Exception\NoConsumerRequestDefined;
use stdClass;

trait UsesPactContextTrait
{
    protected PactContext $pact;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->pact = $environment->getContext(PactContext::class);
    }

    /**
     * @param string $providerName
     * @param string $uri
     * @param array|stdClass $requestBody
     * @param int $responseStatus
     * @param array|stdClass $responseBody
     * @throws ConnectionException
     */
    protected function pactPostInteraction(
        string $providerName,
        string $uri,
        $requestBody,
        int $responseStatus,
        $responseBody = []
    ): void {
        // Create request expectation
        $success = $this->pact->requestToWithParameters(
            $providerName,
            'POST',
            $uri,
            is_array($requestBody) ? $this->createTableNode($requestBody) : $requestBody
        );

        if (!$success) {
            throw new ConnectionException('Unable to create request expectation');
        }

        // and the associated response
        try {
            $this->pact->theProviderRequestShouldReturnResponseWithAndBody(
                $providerName,
                $responseStatus,
                is_array($responseBody) ? $this->createTableNode($responseBody) : $responseBody
            );
        } catch (NoConsumerRequestDefined $ex) {
            throw new ConnectionException('Unable to create response expectation');
        }
    }

    /**
     * @param string $providerName The name of the pact provider to access
     * @param string $uri The url of the request to make
     * @param ?string $query A query string to attach to the url
     * @param int $responseStatus The response code of the mocked response
     * @param array|stdClass $responseBody The response body of the mocked response, either a JSON
     *                                   string or an associative array
     */
    protected function pactGetInteraction(
        string $providerName,
        string $uri,
        int $responseStatus,
        $responseBody = [],
        string $query = ''
    ): void {
        $this->pact->registerInteractionWithQueryAndBody(
            $providerName,
            'GET',
            $uri,
            $query,
            $responseStatus,
            is_array($responseBody) ? $this->createTableNode($responseBody) : $responseBody
        );
    }

    /**
     * Processes a more standard multi-dimensional array into the correct format for a TableNode and returns
     * that TableNode ready to be used.
     *
     * @param array $data
     * @return TableNode
     */
    private function createTableNode(array $data): TableNode
    {
        $processed = array_map(function ($key, $value) {
            return [$key, $value];
        }, array_keys($data), $data);

        array_unshift($processed, ['parameter', 'value']);

        return new TableNode($processed);
    }
}
