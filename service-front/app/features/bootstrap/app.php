<?php

declare(strict_types=1);

/** @var \Psr\Container\ContainerInterface $container */
$container = require 'container.php';

/** @var \Zend\Expressive\Application $app */
$app = $container->get(\Zend\Expressive\Application::class);
$factory = $container->get(\Zend\Expressive\MiddlewareFactory::class);

// Execute programmatic/declarative middleware pipeline and routing
// configuration statements
(require 'config/pipeline.php')($app, $factory, $container);
(require 'config/routes.php')($app, $factory, $container);

return $app;
