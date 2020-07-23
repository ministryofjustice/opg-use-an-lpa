<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;
use Zend\DI\Config\Config;
use Zend\DI\Config\ContainerFactory;

$aggregator = new ConfigAggregator(
    [
        new PhpFileProvider(realpath(__DIR__) . '/../../config/config.php'),

        // Load development config if it exists
        new PhpFileProvider(realpath(__DIR__) . '/config.php')
    ]
);

$factory = new ContainerFactory();

return $factory(new Config($aggregator->getMergedConfig()));
