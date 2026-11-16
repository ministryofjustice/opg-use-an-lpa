<?php

declare(strict_types=1);

use Telemetry\Tracer;

// Because of the way telemetry works our best bet when it comes to the behat tests
// is to just set it up and use it. It's all static accessor methods and globals
// which are a pain to mock or otherwise workaround.
Tracer::initialise();
