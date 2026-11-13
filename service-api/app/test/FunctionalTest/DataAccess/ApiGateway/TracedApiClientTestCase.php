<?php

declare(strict_types=1);

namespace FunctionalTest\DataAccess\ApiGateway;

use FunctionalTest\AbstractFunctionalTestCase;
use OpenTelemetry\Context\ScopeInterface;
use OpenTelemetry\Contrib\Aws\Xray\Propagator;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Telemetry\Instrumentation\Guzzle;

abstract class TracedApiClientTestCase extends AbstractFunctionalTestCase
{
    private ScopeInterface $telemetryScope;

    public function setUp(): void
    {
        $this->telemetryScope = Sdk::builder()
            ->setTracerProvider(new TracerProvider())
            ->setPropagator(new Propagator())
            ->buildAndRegisterGlobal();

        Guzzle::register();

        parent::setUp();
    }

    public function tearDown(): void
    {
        $this->telemetryScope->detach();

        parent::tearDown();
    }
}
