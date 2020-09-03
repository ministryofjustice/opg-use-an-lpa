<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

$aggregator = new ConfigAggregator([
    new PhpFileProvider(realpath(__DIR__) . '/../../config/config.php'),

    // Load development config if it exists
    new PhpFileProvider(realpath(__DIR__) . '/behat.config.php'),

    // Include cache configuration
    new ArrayProvider(
        [
            'debug' => true,
            ConfigAggregator::ENABLE_CACHE => false,
        ]
    ),
]);

return $aggregator->getMergedConfig();
