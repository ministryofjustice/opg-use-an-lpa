<?php

declare(strict_types=1);

namespace Telemetry;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use OpenTelemetry\Contrib\Aws\Ecs\DataProvider;
use OpenTelemetry\Contrib\Aws\Ecs\Detector;
use OpenTelemetry\Contrib\Aws\Xray\Propagator;
use OpenTelemetry\SDK\Registry;
use OpenTelemetry\SDK\Sdk;
use Telemetry\Instrumentation\Aws;
use Telemetry\Instrumentation\Exporter;
use Telemetry\Instrumentation\Guzzle;
use Telemetry\Instrumentation\PSR15Handler;
use Telemetry\Resource\CachingDetector;

/**
 * Registers OpenTelemetry X-Ray propagation and the hook based instrumentation used by the service.
 *
 * Must be called as early as possible (immediately after the composer autoloader) so that registrations
 * happen before the OpenTelemetry globals are lazily initialised.
 *
 * @psalm-api Called from public/index.php
 */
class Tracer
{
    public static function initialise(): void
    {
        if (!extension_loaded('opentelemetry') || Sdk::isDisabled()) {
            return;
        }

        Registry::registerTextMapPropagator('xray', new Propagator());
        Registry::registerResourceDetector(
            'aws',
            new CachingDetector(
                'ecs',
                new Detector(new DataProvider(), new Client(['timeout' => 1]), new HttpFactory()),
            ),
        );

        Exporter::register();
        Aws::register();
        Guzzle::register();
        PSR15Handler::register();
    }
}
