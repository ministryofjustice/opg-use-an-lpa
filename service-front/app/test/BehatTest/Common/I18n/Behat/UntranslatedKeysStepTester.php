<?php

declare(strict_types=1);

namespace BehatTest\Common\I18n\Behat;

use Behat\Behat\Context\Environment\ContextEnvironment;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Behat\Tester\StepTester;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Mink\Exception\DriverException;
use Behat\Testwork\Call\CallResult;
use Behat\Testwork\Environment\Environment;
use BehatTest\Context\UI\BaseUiContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Collects the untranslated keys rendered by each step of a scenario and fails the scenario's last step if any
 * were found.
 *
 * Failing as a step result, rather than from a hook, matters. Behat retains the scope, and so the whole scenario
 * environment, of every failed hook until the run ends; enough failures will exhaust memory.
 */
final class UntranslatedKeysStepTester implements StepTester, EventSubscriberInterface
{
    public const string OPT_OUT_TAG = 'allow-untranslated-keys';

    private bool $optedOut     = false;
    private ?int $lastStepLine = null;

    /** @var array<string, array<string, list<string>>> Missing keys indexed by step and the page it ended on */
    private array $missing = [];

    public function __construct(private readonly StepTester $baseTester)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScenarioTested::BEFORE => 'startScenario',
            ExampleTested::BEFORE  => 'startScenario',
        ];
    }

    public function startScenario(BeforeScenarioTested $event): void
    {
        $steps = $event->getScenario()->getSteps();

        $this->missing      = [];
        $this->lastStepLine = $steps === [] ? null : end($steps)->getLine();
        $this->optedOut     = $event->getScenario()->hasTag(self::OPT_OUT_TAG)
            || $event->getFeature()->hasTag(self::OPT_OUT_TAG);
    }

    public function setUp(Environment $env, FeatureNode $feature, StepNode $step, $skip)
    {
        return $this->baseTester->setUp($env, $feature, $step, $skip);
    }

    public function test(Environment $env, FeatureNode $feature, StepNode $step, $skip)
    {
        $result = $this->baseTester->test($env, $feature, $step, $skip);

        if ($skip || $this->optedOut || !$env instanceof ContextEnvironment) {
            return $result;
        }

        if (!$env->hasContextClass(BaseUiContext::class)) {
            return $result;
        }

        $context = $env->getContext(BaseUiContext::class);
        $missing = $context->flushUntranslatedKeys();
        if ($missing !== []) {
            $stepName = sprintf('%s %s (line %d)', $step->getKeyword(), $step->getText(), $step->getLine());

            $this->missing[$stepName] = [$this->currentPage($context) => $missing];
        }

        if ($step->getLine() !== $this->lastStepLine || $this->missing === []) {
            return $result;
        }

        return $this->failed($result);
    }

    public function tearDown(Environment $env, FeatureNode $feature, StepNode $step, $skip, StepResult $result)
    {
        return $this->baseTester->tearDown($env, $feature, $step, $skip, $result);
    }

    /**
     * The page the step ended on. A step that passes through several pages may have rendered a key on an earlier one.
     */
    private function currentPage(BaseUiContext $context): string
    {
        try {
            $url = $context->getSession()->getCurrentUrl();
        } catch (DriverException) {
            return 'no page loaded';
        }

        $query = parse_url($url, PHP_URL_QUERY);

        return (parse_url($url, PHP_URL_PATH) ?? '/') . (is_string($query) ? '?' . $query : '');
    }

    private function failed(StepResult $result): StepResult
    {
        // A step that has already failed takes precedence
        if (!$result instanceof ExecutedStepResult || !$result->isPassed()) {
            return $result;
        }

        $message = 'Translation keys missing from the catalog were rendered by this scenario:';
        foreach ($this->missing as $step => $pages) {
            $message .= "\n" . $step;
            foreach ($pages as $page => $keys) {
                $message .= "\n  " . $page . "\n    " . implode("\n    ", $keys);
            }
        }

        $callResult = $result->getCallResult();

        return new ExecutedStepResult(
            $result->getSearchResult(),
            new CallResult(
                $callResult->getCall(),
                $callResult->getReturn(),
                new UntranslatedKeysException($message),
                $callResult->getStdOut(),
            ),
        );
    }
}
