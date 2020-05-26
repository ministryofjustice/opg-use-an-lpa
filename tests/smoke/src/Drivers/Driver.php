<?php

declare(strict_types=1);

namespace Smoke\Drivers;

interface Driver
{
    public const DRIVER_TAG = "smokedriver.driver";

    public function start(): void;
    public function stop(): void;
    public function isRunning(): bool;
}
