<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Acpr\Behat\Psr\Context\Psr11MinkAwareContext;
use Acpr\Behat\Psr\Context\RuntimeMinkContext;
use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use Common\Service\Pdf\StylesService;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Psr\Container\ContainerInterface;

use function random_bytes;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

class BaseUiContext extends RawMinkContext implements Psr11MinkAwareContext
{
    use RuntimeMinkContext;

    public ContainerInterface $container;
    public MockHandler $apiFixtures;
    public AwsMockHandler $awsFixtures;
    private ErrorHandler $errorHandler;
    public array $mockClientHistoryContainer = [];
    public MinkContext $ui;

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
