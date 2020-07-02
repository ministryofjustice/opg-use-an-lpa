<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\Service\Log\RequestTracing;
use Aws\MockHandler as AwsMockHandler;
use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Behat\Hook\Scope\StepScope;
use Behat\Testwork\Hook\Scope\AfterTestScope;
use BehatTest\Context\SetupEnv;
use GuzzleHttp\Client as HttpClient;
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

    /**
     * @var Pact
     */
    private Pact $pact;

    /**
     * @var Pact
     * Required for AfterSuite operations
     */
    private static Pact $pactStatic;

    /**
     * @var Authenticator
     */
    private Authenticator $authenticator;

    /**
     * @var ProviderState
     */
    private ProviderState $providerState;

    private $httpClient;

    private $response;

    /**
     * @var string
     */
    private string $baseUrl;

    /**
     * @var string
     */
    private string $uri;

    /**
     * @var string
     */
    private string $providerName;

    /**
     * @var string
     */
    private string $stepName;

    /**
     * @var array
     */
    private array $tags = [];

    /**
     * @var array
     */
    private array $consumerRequest = [];

    /**
     * @var array
     */
    private array $headers = [];

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
        // Required for AfterSuite cleanup to finalize results
        self::$pactStatic = $pact;

        $this->httpClient = new HttpClient();

        // Defined in behat.config.php
        $this->providerName = ;
        $this->baseUrl = ; // TODO Access values via behat.config.php
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
     */
    public function iShouldReceiveTheStatusOfTheAPI()
    {
        if (!isset($this->consumerRequest[$this->providerName])) {
            throw new NoConsumerRequestDefined(
                'No consumer InteractionRequestDTO defined.'
            );
        }

        $response      = new InteractionResponseDTO(200);
        $request       = $this->consumerRequest[$this->providerName];
        $providerState = $this->providerState->getStateDescription($this->providerName);
        unset($this->consumerRequest[$this->providerName]);

        $this->pact->registerInteraction($request, $response, $providerState);

        $this->response = $this->httpClient->get($this->baseUrl . $this->uri, [
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $body = $this->response->getBody()->getContents();
    }

    /**
     * @When /^I request to add an LPA$/
     */
    public function iRequestToAddAnLPA()
    {
        $this->uri = '/v1/validate';

        $headers = $this->getHeaders($this->providerName);

        $this->consumerRequest[$this->providerName] = new InteractionRequestDTO(
            $this->providerName,
            $this->stepName,
            $this->uri,
            'POST',
            $headers
        );
    }

    /**
     * @Then /^I should be told my code is valid$/
     */
    public function iShouldBeToldMyCodeIsValid()
    {
        if (!isset($this->consumerRequest[$this->providerName])) {
            throw new NoConsumerRequestDefined(
                'No consumer InteractionRequestDTO defined.'
            );
        }

        $parameters = [
            'actor' => [
                'value' => 'a95a0543-6e9e-4fd5-9c77-94eb1a8f4da6'
            ]
        ];

        // Matcher provided by SmartGamma\Behat\PactExtension\Infrastructure\Interaction\MatcherInterface
        $response = [
            'actor' => [
                'value' => 'a95a0543-6e9e-4fd5-9c77-94eb1a8f4da6',
                'match' => 'uuid'
            ]
        ];

        $response      = new InteractionResponseDTO(200, $parameters, $response);
        $request       = $this->consumerRequest[$this->providerName];
        $providerState = $this->providerState->getStateDescription($this->providerName);
        unset($this->consumerRequest[$this->providerName]);

        $this->pact->registerInteraction($request, $response, $providerState);

        $this->response = $this->httpClient->post($this->baseUrl . $this->uri, [
            'json'     => [
                'lpa'  => 'eed4f597-fd87-4536-99d0-895778824861',
                'dob'  => '1960-06-05',
                'code' => 'YSSU4IAZTUXM'
            ],
            'headers'  => ['Content-Type' => 'application/json']
        ]);

        $body = $this->response->getBody()->getContents();
    }

    /**
     * @BeforeScenario
     */
    public function setupBehatTags(ScenarioScope $scope): void
    {
        $this->tags = $scope->getScenario()->getTags();
        $this->providerState->clearStates();
    }

    /**
     * @BeforeScenario
     */
    public function setupBehatStepName(ScenarioScope $step): void
    {
        if ($step->getScenario()->getTitle()) {
            $this->providerState->setDefaultPlainTextState($step->getScenario()->getTitle());
        }
    }

    /**
     * @BeforeStep
     */
    public function setupBehatScenarioName(StepScope $step): void
    {
        $this->stepName = $step->getStep()->getText();
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

    /**
     * @AfterSuite
     */
    public static function teardown(AfterTestScope $scope): bool
    {
        if (!$scope->getTestResult()->isPassed()) {
            echo 'A test has failed. Skipping PACT file upload.';

            return false;
        }

        return self::$pactStatic->finalize(self::$pactStatic->getConsumerVersion());
    }

    private function getHeaders(string $providerName): array
    {
        return isset($this->headers[$providerName]) ? $this->headers[$providerName] : [];
    }
}
