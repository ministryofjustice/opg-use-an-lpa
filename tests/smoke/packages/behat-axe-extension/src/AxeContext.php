<?php

declare(strict_types=1);

namespace BehatAxeExtension;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Behat\MinkExtension\Context\MinkContext;

class AxeContext implements Context
{
    private ?MinkContext $ui = null;

    public function __construct(private string $axeOutputPath)
    {
    }

    #[BeforeScenario]
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        /** @psalm-var InitializedContextEnvironment $environment */
        $environment = $scope->getEnvironment();
        $this->ui    = $environment->getContext(MinkContext::class);
    }

    #[AfterScenario]
    public function axeResults(AfterScenarioScope $scope): void
    {
        $results = $this->ui?->getSession()->getDriver()->getAxeResults();

        foreach ($results as $path => $pageResults) {
            if (empty($pageResults)) {
                continue;
            }

            $output = sprintf(
                "## %s%s - *%s*\n%s\n",
                $scope->getSuite()->getName(),
                $path,
                $scope->getScenario()->getTitle(),
                AxeResultMarkdownParser::parseAll($pageResults),
            );

            print $output;
            file_put_contents($this->axeOutputPath, $output, FILE_APPEND);
        }
    }
}
