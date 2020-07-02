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

    private string $baseUrl;

//    private string $uri;

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

    /**
     * @var Pact
     * Required for AfterSuite operations
     */
    private static $pactStatic;

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
        // Required for AfterSuite cleanup to finalize results
        self::$pactStatic = $pact;

        $this->httpClient = new HttpClient();

        // Defined in Behat.yml
        $this->providerName = 'lpa-codes-pact-mock';
        $this->baseUrl = 'lpa-codes-pact-mock:80'; // TODO App config..later
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
        $headers = $this->getHeaders($this->providerName);

        $this->consumerRequest[$this->providerName] = new InteractionRequestDTO(
            $this->providerName,
            $this->stepName,
            '/v1/healthcheck',
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

        $request       = $this->consumerRequest[$this->providerName];
        $response      = new InteractionResponseDTO(200);
        $providerState = $this->providerState->getStateDescription($this->providerName);
        unset($this->consumerRequest[$this->providerName]);

        $this->pact->registerInteraction($request, $response, $providerState);

        $this->response = $this->httpClient->post($this->baseUrl . '/v1/healthcheck', [
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $body = $this->response->getBody()->getContents();
    }

    /**
     * @When /^I request to add an LPA$/
     */
    public function iRequestToAddAnLPA()
    {
//        $this->uri = '/v1/validate';
        $headers = $this->getHeaders($this->providerName);

        $this->consumerRequest[$this->providerName] = new InteractionRequestDTO(
            $this->providerName,
            $this->stepName,
            '/v1/validate',
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

        $request       = $this->consumerRequest[$this->providerName];
        $providerState = $this->providerState->getStateDescription($this->providerName);

        $response      = new InteractionResponseDTO(200, $parameters, $response);
        unset($this->consumerRequest[$this->providerName]);

        $this->pact->registerInteraction($request, $response, $providerState);

        $this->response = $this->httpClient->post($this->baseUrl . '/v1/validate', [
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
