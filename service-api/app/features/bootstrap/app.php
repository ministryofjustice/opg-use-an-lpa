<?php

declare(strict_types=1);

/** @var \Mezzio\Application $app */
$app = $container->get(\Mezzio\Application::class);
$factory = $container->get(\Mezzio\MiddlewareFactory::class);

// Execute programmatic/declarative middleware pipeline and routing
// configuration statements
(require 'config/pipeline.php')($app, $factory, $container);
(require 'config/routes.php')($app, $factory, $container);

return $app;
