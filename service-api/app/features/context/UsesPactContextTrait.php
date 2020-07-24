<?php

declare(strict_types=1);

namespace BehatTest\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use PhpPact\Exception\ConnectionException;
use SmartGamma\Behat\PactExtension\Context\PactContext;

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
     * @param array $requestBody
     * @param int $responseStatus
     * @param array $responseBody
     * @throws ConnectionException
     * @throws \SmartGamma\Behat\PactExtension\Exception\NoConsumerRequestDefined
     */
    protected function pactPostInteraction(
        string $providerName,
        string $uri,
        array $requestBody,
        int $responseStatus,
        array $responseBody
    ): void {
        // Create request expectation
        $success = $this->pact->requestToWithParameters(
            $providerName,
            'POST',
            $uri,
            $this->createTableNode($requestBody)
        );

        if (!$success) {
            throw new ConnectionException('Unable to create request expectation');
        }

        // and the associated response
        $this->pact->theProviderRequestShouldReturnResponseWithAndBody(
            $providerName,
            $responseStatus,
            $this->createTableNode($responseBody)
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
