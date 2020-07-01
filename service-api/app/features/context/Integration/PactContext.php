<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\Exception\ApiException;
use App\Service\Log\RequestTracing;
use Aws\MockHandler as AwsMockHandler;
use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Behat\Hook\Scope\StepScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use BehatTest\Context\SetupEnv;
use GuzzleHttp\Client;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use JSHayes\FakeRequests\MockHandler;
use SmartGamma\Behat\PactExtension\Context\Authenticator;
use SmartGamma\Behat\PactExtension\Context\PactContextInterface;
use SmartGamma\Behat\PactExtension\Exception\NoConsumerRequestDefined;
use SmartGamma\Behat\PactExtension\Infrastructure\Interaction\InteractionRequestDTO;
use SmartGamma\Behat\PactExtension\Infrastructure\Interaction\InteractionResponseDTO;
use SmartGamma\Behat\PactExtension\Infrastructure\Pact;
use SmartGamma\Behat\PactExtension\Infrastructure\ProviderState\ProviderState;

/**
 * Class PactContext
 *
 * @package BehatTest\Context\Integration
 *
 */
class PactContext extends BaseIntegrationContext implements PactContextInterface
{
    use SetupEnv;

    /** @var MockHandler */
    private MockHandler $apiFixtures;

    /** @var AwsMockHandler */
    private AwsMockHandler $awsFixtures;

    private string $baseUrl;

    private string $uri;

    private string $providerName;

    private $httpClient;

    private $response;

    /**
     * @var string
     */
    private string $stepName;

    /**
     * @var array
     */
    private $tags = [];

    /**
     * @var Pact
     */
    private $pact;

    private static $pact_static;
    /**
     * @var ProviderState
     */
    private $providerState;

    /**
     * @var array
     */
    private $consumerRequest = [];

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var Authenticator
     */
    private $authenticator;

    /**
     * @var array
     */
    private $matchingObjectStructures = [];

    /**
     * @param Pact          $pact
     * @param ProviderState $providerState
     * @param Authenticator $authenticator
     */
    public function initialize(Pact $pact, ProviderState $providerState, Authenticator $authenticator): void
    {
        $this->pact           = $pact;
        $this->providerState  = $providerState;
        $this->authenticator  = $authenticator;
        $this->stepName       = __FUNCTION__;

        $this->httpClient = new HttpClient();

        // Defined in Behat.yml
        $this->providerName = 'lpa-codes-pact-mock';
        $this->baseUrl = 'lpa-codes-pact-mock:80'; // App config..later
    }

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        $this->apiFixtures = $this->container->get(MockHandler::class);
        $this->awsFixtures = $this->container->get(AwsMockHandler::class);
    }

    /**
     * @BeforeScenario
     */
    public function finalizeInteractions(ScenarioScope $scope): void
    {
        $this->tags = $scope->getFeature()->getTags();
        $this->pact->finalize($this->pact->getConsumerVersion());
    }

    /**
     * @When /^I request the status of the API HealthCheck EndPoint$/
     */
    public function iRequestTheStatusOfTheAPIHealthCheck()
    {
        $this->uri = '/v1/healthcheck';
        $headers = $this->getHeaders($this->providerName);

        $this->consumerRequest[$this->providerName] = new InteractionRequestDTO(
            $this->providerName,
            $this->stepName,
            $this->uri,
            'GET',
            $headers
        );
    }

    /**
     * @Then /^I should receive the status of the API$/
     * @throws NoConsumerRequestDefined
     */
    public function iShouldReceiveTheStatusOfTheAPI()
    {
        if (!isset($this->consumerRequest[$this->providerName])) {
            throw new NoConsumerRequestDefined(
                'No consumer InteractionRequestDTO defined.'
            );
        }

        $request       = $this->consumerRequest[$this->providerName];
        $response      = new InteractionResponseDTO(200);
        $providerState = $this->providerState->getStateDescription($this->providerName);
        unset($this->consumerRequest[$this->providerName]);

        $this->pact->registerInteraction($request, $response, $providerState);

        $this->response = $this->httpClient->get($this->baseUrl . $this->uri, [
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $body = $this->response->getBody()->getContents();
    }

    /**
     * @AfterScenario
     */
    public function verifyInteractions(): void
    {
        if (\in_array('pact', $this->tags, true)) {
            $this->pact->verifyInteractions();
        }
    }

    private function getHeaders(string $providerName): array
    {
        return isset($this->headers[$providerName]) ? $this->headers[$providerName] : [];
    }
}
