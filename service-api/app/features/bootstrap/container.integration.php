<?php

declare(strict_types=1);

use Elie\PHPDI\Config\Config;
use Elie\PHPDI\Config\ContainerFactory;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

$aggregator = new ConfigAggregator(
    [
        new PhpFileProvider(realpath(__DIR__) . '/../../config/config.php'),

        // Load development config if it exists
        new PhpFileProvider(realpath(__DIR__) . '/config.php'),

        // Add in Pact specific configuration
        new PhpFileProvider(realpath(__DIR__) . '/config.pact.php'),
    ]
);

$factory = new ContainerFactory();

return $factory(new Config($aggregator->getMergedConfig()));
