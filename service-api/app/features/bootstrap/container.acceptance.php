<?php

declare(strict_types=1);

use Elie\PHPDI\Config\Config;
use Elie\PHPDI\Config\ContainerFactory;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

require_once __DIR__ . '/telemetry.php';

$aggregator = new ConfigAggregator(
    [
        new PhpFileProvider(realpath(__DIR__) . '/../../config/config.php'),

        // Load development config if it exists
        new PhpFileProvider(realpath(__DIR__) . '/config.php'),
    ]
);

$factory = new ContainerFactory();

return $factory(new Config($aggregator->getMergedConfig()));
