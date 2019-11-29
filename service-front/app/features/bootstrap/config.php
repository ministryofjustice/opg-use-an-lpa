<?php

declare(strict_types=1);

use Zend\ConfigAggregator\ArrayProvider;
use Zend\ConfigAggregator\ConfigAggregator;
use Zend\ConfigAggregator\PhpFileProvider;

$aggregator = new ConfigAggregator([
    new PhpFileProvider(realpath(__DIR__) . '/../../config/config.php'),

    // Load development config if it exists
    new PhpFileProvider(realpath(__DIR__) . '/behat.config.php'),
]);

return $aggregator->getMergedConfig();
