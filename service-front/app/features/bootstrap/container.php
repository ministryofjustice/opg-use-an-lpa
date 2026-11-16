<?php

declare(strict_types=1);

use Elie\PHPDI\Config\Config;
use Elie\PHPDI\Config\ContainerFactory;

require_once __DIR__ . '/telemetry.php';

$config = require __DIR__ . '/config.php';

$factory = new ContainerFactory();

return $factory(new Config($config));
