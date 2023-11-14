<?php

declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;

/** @var \Mezzio\Application $app */
$app     = $container->get(Application::class);
$factory = $container->get(MiddlewareFactory::class);

// Execute programmatic/declarative middleware pipeline and routing
// configuration statements
(require 'config/pipeline.php')($app, $factory, $container);
(require 'config/routes.php')($app, $factory, $container);

return $app;
