<?php

declare(strict_types=1);

namespace Smoke\Decorator;

use Behat\Behat\Tester\ScenarioTester;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface as Scenario;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Tester\Result\TestResult;

class FeatureFlagScenarioSkipper implements ScenarioTester
{
    public function __construct(private ScenarioTester $scenarioTester)
    {
    }

    /**
     * @inheritDoc
     */
    public function setUp(Environment $env, FeatureNode $feature, Scenario $scenario, $skip)
    {
        return $this->scenarioTester->setUp($env, $feature, $scenario, $skip);
    }

    /**
     * @inheritDoc
     */
    public function test(Environment $env, FeatureNode $feature, Scenario $scenario, $skip)
    {
         return $this->scenarioTester->test($env, $feature, $scenario, $this->shouldSkip($scenario));
    }

    /**
     * @inheritDoc
     */
    public function tearDown(Environment $env, FeatureNode $feature, Scenario $scenario, $skip, TestResult $result)
    {
        return $this->scenarioTester->tearDown($env, $feature, $scenario, $skip, $result);
    }

    private function shouldSkip(Scenario $scenario): bool
    {
        foreach ($scenario->getTags() as $tag) {
            if (str_contains($tag, 'ff:')) {
                $tagParts = explode(':', $tag);

                $env = getenv(sprintf('BEHAT_FF_%s', strtoupper($tagParts[1])));
                if ($tagParts[2] !== 'from_env' && $env !== $tagParts[2]) {
                    printf("  ┌─ Feature flag %s set to %s, skipping\n  |\n", $tagParts[1], $env);

                    return true;
                }
            }
        }

        return false;
    }
}
