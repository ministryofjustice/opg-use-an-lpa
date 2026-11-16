<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use BehatTest\Context\BaseAcceptanceContextTrait;
use OpenTelemetry\Context\ScopeInterface;
use OpenTelemetry\Contrib\Aws\Xray\Propagator;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\Assert;

class CommonContext implements Context
{
    use BaseAcceptanceContextTrait;

    private const TRACE_HEADER =
        'Root=1-67891233-abcdef012345678912345678;Parent=53995c3f42cd8ad8;Sampled=1';

    private ?ScopeInterface $telemetryScope = null;

    #[Given('/^I attach a tracing header to my requests$/')]
    public function iAttachATracingHeaderToMyRequests(): void
    {
        $this->telemetryScope = Sdk::builder()
            ->setTracerProvider(new TracerProvider())
            ->setPropagator(new Propagator())
            ->buildAndRegisterGlobal();

        $this->ui->getSession()->setRequestHeader('X-Amzn-Trace-Id', self::TRACE_HEADER);
    }

    #[AfterScenario('@logging')]
    public function resetTracingContext(): void
    {
        $this->telemetryScope?->detach();
        $this->telemetryScope = null;
    }

    /**
     * Relies on a previous context steps having set the last request value using
     * {@link BaseUiContextTrait::setLastRequest()}
     */
    #[Then('/^my outbound requests have attached tracing headers$/')]
    public function myOutboundRequestsHaveAttachedTracingHeaders(): void
    {
        $request = $this->getLastRequest();

        $header = $request->getHeaderLine('X-Amzn-Trace-Id');

        Assert::assertMatchesRegularExpression(
            '/^Root=1-67891233-abcdef012345678912345678;Parent=[0-9a-f]{16};Sampled=1$/',
            $header,
        );
        Assert::assertStringNotContainsString('Parent=53995c3f42cd8ad8', $header);
    }
}
