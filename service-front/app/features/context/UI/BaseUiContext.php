<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Acpr\Behat\Psr\Context\Psr11MinkAwareContext;
use Acpr\Behat\Psr\Context\RuntimeMinkContext;
use Acpr\I18n\TranslatorInterface;
use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use BehatTest\Common\I18n\RecordingTranslator;
use Common\Service\Pdf\StylesService;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Container\ContainerInterface;

use function random_bytes;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

class BaseUiContext extends RawMinkContext implements Psr11MinkAwareContext
{
    use RuntimeMinkContext;

    public ContainerInterface $container;
    public MockHandler $apiFixtures;
    public AwsMockHandler $awsFixtures;
    public TranslatorInterface $translator;
    public MinkContext $ui;

    private RecordingTranslator $recordingTranslator;

    /** @var array<int, array{}> */
    public array $mockClientHistoryContainer = [];

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;

        //Create handler stack and push to container
        $mockHandler  = $container->get(MockHandler::class);
        $handlerStack = new HandlerStack($mockHandler);
        $handlerStack->push(Middleware::prepareBody(), 'prepare_body');

        $history = Middleware::history($this->mockClientHistoryContainer);
        $handlerStack->push($history);
        $container->set(HandlerStack::class, $handlerStack);

        $this->apiFixtures = $mockHandler;
        $this->awsFixtures = $container->get(AwsMockHandler::class);

        $this->recordingTranslator = $container->get(TranslatorInterface::class);
        // Assertions translate keys themselves and must not be recorded as page misses
        $this->translator = $this->recordingTranslator->inner();

        $container->set(
            StylesService::class,
            new StylesService('./test/CommonTest/assets/stylesheets/pdf.css'),
        );
    }

    #[BeforeScenario]
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->ui    = $environment->getContext(MinkContext::class);
    }

    /**
     * @return list<string> Missing translation keys rendered since the last call
     */
    public function flushUntranslatedKeys(): array
    {
        return $this->recordingTranslator->flushMissing();
    }

    #[BeforeScenario]
    public function seedFixtures(): void
    {
        // KMS is polled for encryption data on first page load
        $this->awsFixtures->append(
            new Result(
                [
                    'Plaintext'      => random_bytes(32),
                    'CiphertextBlob' => random_bytes(32),
                ]
            )
        );
    }

    #[AfterScenario]
    public function resetSharedState(): void
    {
        SharedState::getInstance()->reset();
    }
}
