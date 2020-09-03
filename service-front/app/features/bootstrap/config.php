<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

$aggregator = new ConfigAggregator([
    new PhpFileProvider(realpath(__DIR__) . '/../../config/config.php'),

    // Load development config if it exists
    new PhpFileProvider(realpath(__DIR__) . '/behat.config.php'),
], null);

return $aggregator->getMergedConfig();
