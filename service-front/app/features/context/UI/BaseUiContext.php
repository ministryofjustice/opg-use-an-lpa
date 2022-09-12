<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Acpr\Behat\Psr\Context\Psr11MinkAwareContext;
use Acpr\Behat\Psr\Context\RuntimeMinkContext;
use Amp\Process\Internal\Posix\Handle;
use App\Service\ApiClient\Client;
use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;
use Common\Service\Pdf\StylesService;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Laminas\Stratigility\Middleware\ErrorHandler;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function DI\get;
use function random_bytes;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * Class BaseUiContext
 *
 * @package BehatTest\Context\UI
 *
 */
class BaseUiContext extends RawMinkContext implements Psr11MinkAwareContext
{
    use RuntimeMinkContext;

    public ContainerInterface $container;
    public MockHandler $apiFixtures;
    public AwsMockHandler $awsFixtures;
    private ErrorHandler $errorHandler;
    public array $historyContainer = [];
    public MinkContext $ui;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;

        //Create handler stack and push to container
        $mockHandler = $container->get(MockHandler::class);
        $handlerStack = HandlerStack::create($mockHandler);
        $history = Middleware::history($this->historyContainer);
        $handlerStack->push($history);
        $handlerStack->remove('http_errors');
        $handlerStack->remove('cookies');
        $handlerStack->remove('allow_redirects');
        $container->set(HandlerStack::class, $handlerStack);

        $this->apiFixtures = $mockHandler;
        $this->awsFixtures = $container->get(AwsMockHandler::class);

        $container->set(StylesService::class, new StylesService('./test/CommonTest/assets/stylesheets/pdf.css'));
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->ui = $environment->getContext(MinkContext::class);
    }

    /**
     * @BeforeScenario
     */
    public function seedFixtures()
    {
        // KMS is polled for encryption data on first page load
        $this->awsFixtures->append(
            new Result(
                [
                    'Plaintext' => random_bytes(32),
                    'CiphertextBlob' => random_bytes(32)
                ]
            )
        );
    }

    /**
     * @AfterScenario
     */
    public function resetSharedState(): void
    {
        SharedState::getInstance()->reset();
    }

}
